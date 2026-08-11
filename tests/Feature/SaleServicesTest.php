<?php

namespace Tests\Feature;

use App\Exports\SaleReportExport;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReceivablePaymentService;
use App\Services\SalePdfService;
use App\Services\SaleReportPdfService;
use App\Services\SaleReportService;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleServicesTest extends TestCase
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

    protected function kasir(): User
    {
        return User::where('email', 'kasir@hutang.test')->firstOrFail();
    }

    public function test_cash_sale_creates_sale_and_items(): void
    {
        $product = Product::create(['name' => 'Kain Test', 'price' => 45000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Tunai Test', 'phone' => '081300000010']);

        $sale = app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 100000,
            'sale_date' => today()->toDateString(),
        ], $this->kasir());

        $this->assertStringStartsWith('SLS-', $sale->transaction_number);
        $this->assertEquals(Sale::PAYMENT_METHOD_CASH, $sale->payment_method);
        $this->assertEquals($party->id, $sale->receivable_party_id);
        $this->assertEquals(90000, (float) $sale->total_amount);
        $this->assertEquals(100000, (float) $sale->received_amount);
        $this->assertEquals(10000, (float) $sale->change_amount);
        $this->assertEquals(2, $sale->items()->first()->quantity);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_name' => 'Kain Test',
            'subtotal' => 90000,
        ]);
    }

    public function test_sale_with_custom_price(): void
    {
        $product = Product::create(['name' => 'Kain Custom Price Test', 'price' => 50000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Harga Khusus', 'phone' => '081300000099']);

        $sale = app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'price' => 42000, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 100000,
            'sale_date' => today()->toDateString(),
        ], $this->kasir());

        $this->assertEquals(84000, (float) $sale->total_amount);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'price' => 42000,
            'subtotal' => 84000,
        ]);
    }

    public function test_split_payment_sale_creates_sale(): void
    {
        $product = Product::create(['name' => 'Kain Split Test', 'price' => 50000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Split Test', 'phone' => '081300000088']);

        $sale = app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_SPLIT,
            'receivable_party_id' => $party->id,
            'cash_amount' => 40000,
            'transfer_amount' => 60000,
            'sale_date' => today()->toDateString(),
        ], $this->kasir());

        $this->assertEquals(Sale::PAYMENT_METHOD_SPLIT, $sale->payment_method);
        $this->assertEquals(100000, (float) $sale->total_amount);
        $this->assertEquals(40000, (float) $sale->cash_amount);
        $this->assertEquals(60000, (float) $sale->transfer_amount);
        $this->assertEquals(100000, (float) $sale->received_amount);
        $this->assertEquals(0, (float) $sale->change_amount);
        $this->assertEquals('Tunai + Transfer', $sale->payment_method_label);
    }

    public function test_receivable_sale_handles_soft_deleted_invoice_numbers(): void
    {
        $product = Product::create(['name' => 'Kain Kredit Soft Delete', 'price' => 50000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pembeli Kredit Soft Delete', 'phone' => '081300000999']);

        $receivable1 = app(\App\Services\ReceivableService::class)->createReceivable([
            'receivable_party_id' => $party->id,
            'amount' => 50000,
            'receivable_date' => today()->toDateString(),
        ], $this->kasir());

        $firstInvoiceNumber = $receivable1->invoice_number;

        $receivable1->delete();

        $receivable2 = app(\App\Services\ReceivableService::class)->createReceivable([
            'receivable_party_id' => $party->id,
            'amount' => 50000,
            'receivable_date' => today()->toDateString(),
        ], $this->kasir());

        $this->assertNotEquals($firstInvoiceNumber, $receivable2->invoice_number);
        $this->assertStringStartsWith(substr($firstInvoiceNumber, 0, -4), $receivable2->invoice_number);
    }

    public function test_cash_sale_requires_customer(): void
    {
        $product = Product::create(['name' => 'Kain Tanpa Pelanggan', 'price' => 20000, 'is_active' => true]);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 20000,
        ]);
    }

    public function test_receivable_sale_creates_receivable(): void
    {
        $product = Product::create(['name' => 'Kain Kredit Test', 'price' => 55000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pembeli Kredit Test', 'phone' => '081300000001']);

        $sale = app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_RECEIVABLE,
            'receivable_party_id' => $party->id,
            'sale_date' => today()->toDateString(),
        ], $this->kasir());

        $this->assertEquals(Sale::PAYMENT_METHOD_RECEIVABLE, $sale->payment_method);
        $this->assertNull($sale->received_amount);
        $this->assertNotNull($sale->receivable_id);

        $receivable = $sale->receivable;
        $this->assertEquals($party->id, $receivable->receivable_party_id);
        $this->assertEquals(55000, (float) $receivable->amount);
        $this->assertEquals(Receivable::STATUS_UNPAID, $receivable->status);
        $this->assertStringContainsString($sale->transaction_number, $receivable->description);
    }

    public function test_cash_sale_rejects_insufficient_received_amount(): void
    {
        $product = Product::create(['name' => 'Kain Test', 'price' => 45000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Kurang Bayar', 'phone' => '081300000011']);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 50000,
        ]);
    }

    public function test_sale_rejects_empty_cart(): void
    {
        $party = ReceivableParty::create(['name' => 'Pelanggan Keranjang Kosong', 'phone' => '081300000012']);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([
            'items' => [],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 100000,
        ]);
    }

    public function test_sale_rejects_inactive_product(): void
    {
        $product = Product::create(['name' => 'Kain Nonaktif', 'price' => 10000, 'is_active' => false]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Produk Nonaktif', 'phone' => '081300000013']);

        $this->expectException(ValidationException::class);

        app(SaleService::class)->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 10000,
        ]);
    }

    public function test_delete_sale_removes_related_receivable(): void
    {
        $sale = Sale::where('payment_method', Sale::PAYMENT_METHOD_RECEIVABLE)->firstOrFail();
        $receivableId = $sale->receivable_id;

        app(SaleService::class)->deleteSale($sale);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertSoftDeleted('receivables', ['id' => $receivableId]);
    }

    public function test_delete_receivable_sale_with_payments_blocked(): void
    {
        $sale = Sale::where('payment_method', Sale::PAYMENT_METHOD_RECEIVABLE)->firstOrFail();
        $receivable = $sale->receivable;

        app(ReceivablePaymentService::class)->recordInstallment([
            'receivable_id' => $receivable->id,
            'amount' => 10000,
        ], $this->kasir());

        $this->expectException(ValidationException::class);

        app(SaleService::class)->deleteSale($sale);
    }

    public function test_sale_report_service_returns_summary(): void
    {
        $report = app(SaleReportService::class)->data(today()->toDateString());

        $this->assertNotEmpty($report['date']);
        $this->assertEquals(3, $report['summary']['transactions']);
        $this->assertEquals(859000, $report['summary']['total_revenue']);
        $this->assertEquals(625000, $report['summary']['receivable_revenue']);
        $this->assertGreaterThanOrEqual(1, $report['sales']->count());
        $this->assertGreaterThanOrEqual(1, $report['items']->count());

        foreach ($report['sales'] as $saleRow) {
            $this->assertNotEmpty($saleRow['party'], 'Setiap transaksi laporan harus menampilkan pelanggan.');
        }
    }

    public function test_nota_pdf_generates(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $sale = Sale::firstOrFail();

        $pdf = SalePdfService::nota($sale);
        $this->assertEquals(200, $pdf->getStatusCode());
        $this->assertStringContainsString('Nota-'.$sale->transaction_number, $pdf->headers->get('content-disposition'));
    }

    public function test_daily_report_pdf_and_excel_generate(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $pdf = SaleReportPdfService::generate(today()->toDateString());
        $this->assertEquals(200, $pdf->getStatusCode());

        $xlsx = SaleReportExport::xlsx(today()->toDateString());
        $this->assertEquals(200, $xlsx->getStatusCode());
    }
}
