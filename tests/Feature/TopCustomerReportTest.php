<?php

namespace Tests\Feature;

use App\Exports\TopCustomerReportExport;
use App\Filament\Pages\TopCustomerReport;
use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TopCustomerReportPdfService;
use App\Services\TopCustomerReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopCustomerReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function admin(): User
    {
        return User::where('email', 'admin@hutang.test')->firstOrFail();
    }

    public function test_admin_can_access_top_customer_report_page(): void
    {
        $response = $this->actingAs($this->admin())->get(TopCustomerReport::getUrl());
        $response->assertSuccessful();
        $response->assertSee('Pelanggan dengan Transaksi Terbanyak');
    }

    public function test_top_customer_service_aggregates_and_sorts_correctly(): void
    {
        $product = Product::create(['name' => 'Kain Katun Premium', 'price' => 50000, 'is_active' => true]);

        $customerA = ReceivableParty::create(['name' => 'Pelanggan Sering', 'phone' => '081234567801']);
        $customerB = ReceivableParty::create(['name' => 'Pelanggan Sultan', 'phone' => '081234567802']);

        $saleService = app(SaleService::class);
        $admin = $this->admin();

        // Customer A: 5 transaksi bernilai kecil (5 x Rp 50.000 = Rp 250.000)
        for ($i = 0; $i < 5; $i++) {
            $saleService->createSale([
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => Sale::PAYMENT_METHOD_CASH,
                'receivable_party_id' => $customerA->id,
                'received_amount' => 50000,
                'sale_date' => today()->toDateString(),
            ], $admin);
        }

        // Customer B: 1 transaksi bernilai sangat besar (1 x 50 x Rp 50.000 = Rp 2.500.000)
        $saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 50],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $customerB->id,
            'received_amount' => 2500000,
            'sale_date' => today()->toDateString(),
        ], $admin);

        $reportService = app(TopCustomerReportService::class);

        // 1. Urutkan berdasarkan Frekuensi Transaksi (Default)
        $reportByTxns = $reportService->data([
            'preset' => TopCustomerReportService::PRESET_ALL,
            'sort_by' => TopCustomerReportService::SORT_TRANSACTIONS,
        ]);

        $topTxnCustomer = $reportByTxns['customers']->first();
        // Peringkat 1 harus Customer A (5 transaksi)
        $this->assertEquals($customerA->id, $topTxnCustomer['party_id']);
        $this->assertEquals(5, $topTxnCustomer['transaction_count']);
        $this->assertEquals(250000, (float) $topTxnCustomer['total_spent']);

        // 2. Urutkan berdasarkan Total Omset / Belanja (Revenue)
        $reportByRevenue = $reportService->data([
            'preset' => TopCustomerReportService::PRESET_ALL,
            'sort_by' => TopCustomerReportService::SORT_REVENUE,
        ]);

        $topRevenueCustomer = $reportByRevenue['customers']->first();
        // Peringkat 1 berdasarkan omset harus Customer B (Rp 2.500.000)
        $this->assertEquals($customerB->id, $topRevenueCustomer['party_id']);
        $this->assertEquals(2500000, (float) $topRevenueCustomer['total_spent']);
        $this->assertEquals(50, (float) $topRevenueCustomer['total_quantity']);
    }

    public function test_top_customer_export_excel_returns_valid_download(): void
    {
        $response = TopCustomerReportExport::xlsx([
            'preset' => TopCustomerReportService::PRESET_THIS_MONTH,
            'sort_by' => TopCustomerReportService::SORT_TRANSACTIONS,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan-Pelanggan-Terbanyak', $response->headers->get('Content-Disposition'));
    }

    public function test_top_customer_export_pdf_returns_valid_download(): void
    {
        $response = TopCustomerReportPdfService::generate([
            'preset' => TopCustomerReportService::PRESET_THIS_MONTH,
            'sort_by' => TopCustomerReportService::SORT_TRANSACTIONS,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan-Pelanggan-Terbanyak', $response->headers->get('Content-Disposition'));
    }
}
