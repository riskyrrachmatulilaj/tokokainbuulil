<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentHistory;
use App\Models\User;
use App\Services\CollectivePaymentService;
use App\Services\DebtStatementPdfService;
use App\Services\PaymentService;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_fifo_allocation_prioritises_oldest_debt(): void
    {
        $customer = Customer::create(['name' => 'Pelanggan FIFO Test', 'phone' => '081000000001']);
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        // Nota terlama 300rb, kemudian 500rb
        $old = Debt::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-1',
            'amount' => 300000,
            'paid_amount' => 0,
            'remaining_amount' => 300000,
            'debt_date' => now()->subMonths(2),
            'due_date' => null,
            'description' => 'test',
            'created_by' => $kasir->id,
        ]);
        $new = Debt::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-2',
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'debt_date' => now()->subDays(5),
            'due_date' => null,
            'description' => 'test',
            'created_by' => $kasir->id,
        ]);

        app(CollectivePaymentService::class)->process([
            'customer_id' => $customer->id,
            'amount' => 700000,
            'payment_date' => today(),
        ], $kasir);

        $this->assertEquals(Debt::STATUS_PAID, $old->fresh()->status);
        $this->assertEquals(0, (float) $old->fresh()->remaining_amount);
        $this->assertEquals(100000, (float) $new->fresh()->remaining_amount);
        $this->assertEquals(Debt::STATUS_UNPAID, $new->fresh()->status);
    }

    public function test_installment_rejects_overpayment(): void
    {
        $debt = Debt::where('status', Debt::STATUS_UNPAID)->firstOrFail();
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->recordInstallment([
            'debt_id' => $debt->id,
            'amount' => (float) $debt->remaining_amount + 1,
        ], $kasir);
    }

    public function test_cancel_installment_restores_balance(): void
    {
        $installment = \App\Models\Installment::firstOrFail();
        $debt = $installment->debt()->firstOrFail();
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $before = (float) $debt->fresh()->remaining_amount;

        app(PaymentService::class)->cancelInstallment($installment);

        $after = (float) $debt->fresh()->remaining_amount;
        $this->assertGreaterThan($before, $after);
        $this->assertSoftDeleted('installments', ['id' => $installment->id]);
    }

    public function test_report_service_returns_data_for_all_types(): void
    {
        $service = app(ReportService::class);

        foreach (array_keys(ReportService::TYPES) as $type) {
            $rows = $service->data(['type' => $type]);
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $rows);
            $this->assertNotEmpty($service->title($type));
        }
    }

    public function test_debt_statement_pdf_generates_for_customer(): void
    {
        $admin = User::where('email', 'admin@hutang.test')->firstOrFail();
        $this->actingAs($admin);

        $customer = Customer::with('debts')->has('debts')->firstOrFail();

        $pdf = DebtStatementPdfService::generate($customer);
        $this->assertEquals(200, $pdf->getStatusCode());
        $this->assertStringContainsString('Rincian-Hutang-', $pdf->headers->get('content-disposition'));
    }

    public function test_pdf_and_excel_exports_generate(): void
    {
        $admin = User::where('email', 'admin@hutang.test')->firstOrFail();
        $this->actingAs($admin);

        $filters = ['type' => ReportService::TYPE_DEBT_LIST];

        $pdf = ReportPdfService::generate(ReportService::TYPE_DEBT_LIST, $filters);
        $this->assertEquals(200, $pdf->getStatusCode());

        $xlsx = \App\Exports\ReportExport::xlsx(ReportService::TYPE_DEBT_LIST, $filters);
        $this->assertEquals(200, $xlsx->getStatusCode());
    }
}
