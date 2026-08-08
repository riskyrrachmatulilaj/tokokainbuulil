<?php

namespace Tests\Feature;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\User;
use App\Services\ReceivableCollectivePaymentService;
use App\Services\ReceivableReportService;
use App\Services\ReceivableStatementPdfService;
use App\Services\ReceivablePaymentService;
use App\Services\ReceivableReportPdfService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReceivableServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_fifo_allocation_prioritises_oldest_receivable(): void
    {
        $party = ReceivableParty::create(['name' => 'Debitur FIFO Test', 'phone' => '081000000001']);
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $old = Receivable::create([
            'receivable_party_id' => $party->id,
            'invoice_number' => 'PINV-TEST-1',
            'amount' => 300000,
            'paid_amount' => 0,
            'remaining_amount' => 300000,
            'receivable_date' => now()->subMonths(2),
            'due_date' => null,
            'description' => 'test',
            'created_by' => $kasir->id,
        ]);
        $new = Receivable::create([
            'receivable_party_id' => $party->id,
            'invoice_number' => 'PINV-TEST-2',
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'receivable_date' => now()->subDays(5),
            'due_date' => null,
            'description' => 'test',
            'created_by' => $kasir->id,
        ]);

        app(ReceivableCollectivePaymentService::class)->process([
            'receivable_party_id' => $party->id,
            'amount' => 700000,
            'payment_date' => today(),
        ], $kasir);

        $this->assertEquals(Receivable::STATUS_PAID, $old->fresh()->status);
        $this->assertEquals(0, (float) $old->fresh()->remaining_amount);
        $this->assertEquals(100000, (float) $new->fresh()->remaining_amount);
        $this->assertEquals(Receivable::STATUS_UNPAID, $new->fresh()->status);
    }

    public function test_installment_rejects_overpayment(): void
    {
        $receivable = Receivable::where('status', Receivable::STATUS_UNPAID)->firstOrFail();
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(ReceivablePaymentService::class)->recordInstallment([
            'receivable_id' => $receivable->id,
            'amount' => (float) $receivable->remaining_amount + 1,
        ], $kasir);
    }

    public function test_cancel_installment_restores_balance(): void
    {
        $installment = \App\Models\ReceivableInstallment::firstOrFail();
        $receivable = $installment->receivable()->firstOrFail();
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $before = (float) $receivable->fresh()->remaining_amount;

        app(ReceivablePaymentService::class)->cancelInstallment($installment);

        $after = (float) $receivable->fresh()->remaining_amount;
        $this->assertGreaterThan($before, $after);
        $this->assertSoftDeleted('receivable_installments', ['id' => $installment->id]);
    }

    public function test_report_service_returns_data_for_all_types(): void
    {
        $service = app(ReceivableReportService::class);

        foreach (array_keys(ReceivableReportService::TYPES) as $type) {
            $rows = $service->data(['type' => $type]);
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $rows);
            $this->assertNotEmpty($service->title($type));
        }
    }

    public function test_receivable_statement_pdf_generates_for_party(): void
    {
        $admin = User::where('email', 'admin@hutang.test')->firstOrFail();
        $this->actingAs($admin);

        $party = ReceivableParty::with('receivables')->has('receivables')->firstOrFail();

        $pdf = ReceivableStatementPdfService::generate($party);
        $this->assertEquals(200, $pdf->getStatusCode());
        $this->assertStringContainsString('Rincian-Piutang-', $pdf->headers->get('content-disposition'));
    }

    public function test_pdf_and_excel_exports_generate(): void
    {
        $admin = User::where('email', 'admin@hutang.test')->firstOrFail();
        $this->actingAs($admin);

        $filters = ['type' => ReceivableReportService::TYPE_RECEIVABLE_LIST];

        $pdf = ReceivableReportPdfService::generate(ReceivableReportService::TYPE_RECEIVABLE_LIST, $filters);
        $this->assertEquals(200, $pdf->getStatusCode());

        $xlsx = \App\Exports\ReceivableReportExport::xlsx(ReceivableReportService::TYPE_RECEIVABLE_LIST, $filters);
        $this->assertEquals(200, $xlsx->getStatusCode());
    }
}
