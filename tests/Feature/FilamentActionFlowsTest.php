<?php

namespace Tests\Feature;

use App\Filament\Pages\CollectivePaymentPage;
use App\Filament\Pages\Reports;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\DebtsRelationManager;
use App\Filament\Resources\DebtResource\Pages\EditDebt;
use App\Filament\Resources\DebtResource\RelationManagers\InstallmentsRelationManager;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentHistory;
use App\Models\User;
use App\Services\DebtService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentActionFlowsTest extends TestCase
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

    public function test_installments_relation_manager_records_installment(): void
    {
        $debt = Debt::where('status', Debt::STATUS_UNPAID)->firstOrFail();
        $before = (float) $debt->remaining_amount;
        $amount = 10000;

        Livewire::actingAs($this->admin())
            ->test(InstallmentsRelationManager::class, [
                'ownerRecord' => $debt,
                'pageClass' => EditDebt::class,
            ])
            ->callTableAction('create', data: [
                'amount' => $amount,
                'installment_date' => today()->toDateString(),
            ]);

        $this->assertDatabaseHas('installments', [
            'debt_id' => $debt->id,
            'amount' => $amount,
        ]);

        $this->assertDatabaseHas('payment_histories', [
            'debt_id' => $debt->id,
            'payment_type' => PaymentHistory::TYPE_INSTALLMENT,
            'amount' => $amount,
        ]);

        $debt->refresh();
        $this->assertEquals($before - $amount, (float) $debt->remaining_amount);
    }

    public function test_installments_relation_manager_rejects_overpayment(): void
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Overpay Test',
            'phone' => '081200000001',
        ]);
        $debt = app(DebtService::class)->createDebt(['customer_id' => $customer->id, 'amount' => 300000]);
        $overpay = (float) $debt->remaining_amount + 1;

        Livewire::actingAs($this->admin())
            ->test(InstallmentsRelationManager::class, [
                'ownerRecord' => $debt,
                'pageClass' => EditDebt::class,
            ])
            ->callTableAction('create', data: [
                'amount' => $overpay,
                'installment_date' => today()->toDateString(),
            ])
            ->assertTableActionHalted('create');

        $this->assertDatabaseMissing('installments', [
            'debt_id' => $debt->id,
        ]);

        $debt->refresh();
        $this->assertEquals((float) $debt->amount, (float) $debt->remaining_amount);
    }

    public function test_debts_relation_manager_creates_debt(): void
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Nota Test',
            'phone' => '081200000002',
        ]);

        Livewire::actingAs($this->admin())
            ->test(DebtsRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditCustomer::class,
            ])
            ->callTableAction('create', data: [
                'amount' => 350000,
                'debt_date' => today()->toDateString(),
            ]);

        $this->assertDatabaseHas('debts', [
            'customer_id' => $customer->id,
            'amount' => 350000,
            'paid_amount' => 0,
            'remaining_amount' => 350000,
            'status' => Debt::STATUS_UNPAID,
        ]);

        $debt = $customer->debts()->firstOrFail();
        $this->assertStringStartsWith('INV-', $debt->invoice_number);
    }

    public function test_cancel_installment_via_relation_manager_restores_balance(): void
    {
        $debt = Debt::where('status', Debt::STATUS_UNPAID)->firstOrFail();
        $beforePaid = (float) $debt->paid_amount;

        $installment = app(PaymentService::class)->recordInstallment([
            'debt_id' => $debt->id,
            'amount' => 10000,
            'installment_date' => today()->toDateString(),
        ], $this->admin());

        $debt->refresh();
        $this->assertEquals($beforePaid + 10000, (float) $debt->paid_amount);

        Livewire::actingAs($this->admin())
            ->test(InstallmentsRelationManager::class, [
                'ownerRecord' => $debt,
                'pageClass' => EditDebt::class,
            ])
            ->callTableAction('delete', $installment);

        $this->assertSoftDeleted('installments', ['id' => $installment->id]);

        $debt->refresh();
        $this->assertEquals($beforePaid, (float) $debt->paid_amount);
        $this->assertEquals((float) $debt->amount - $beforePaid, (float) $debt->remaining_amount);

        $this->assertDatabaseMissing('payment_histories', [
            'installment_id' => $installment->id,
        ]);
    }

    public function test_collective_payment_page_processes_fifo_allocation(): void
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Kolektif Test',
            'phone' => '081200000003',
        ]);
        $service = app(DebtService::class);
        $oldest = $service->createDebt(['customer_id' => $customer->id, 'amount' => 400000, 'debt_date' => today()->subDays(2)]);
        $newer = $service->createDebt(['customer_id' => $customer->id, 'amount' => 600000, 'debt_date' => today()->subDay()]);

        Livewire::actingAs($this->admin())
            ->test(CollectivePaymentPage::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'payment_date' => today()->toDateString(),
                'amount' => 500000,
                'description' => 'Bayar sebagian dua nota',
            ])
            ->call('process');

        $oldest->refresh();
        $newer->refresh();

        $this->assertEquals(400000, (float) $oldest->paid_amount);
        $this->assertEquals(0, (float) $oldest->remaining_amount);
        $this->assertEquals(Debt::STATUS_PAID, $oldest->status);

        $this->assertEquals(100000, (float) $newer->paid_amount);
        $this->assertEquals(500000, (float) $newer->remaining_amount);
        $this->assertEquals(Debt::STATUS_UNPAID, $newer->status);

        $this->assertDatabaseHas('collective_payments', [
            'customer_id' => $customer->id,
            'amount' => 500000,
        ]);

        $this->assertDatabaseHas('payment_histories', [
            'customer_id' => $customer->id,
            'payment_type' => PaymentHistory::TYPE_COLLECTIVE,
        ]);
    }

    public function test_collective_payment_page_rejects_amount_above_total_remaining(): void
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Kolektif Overpay',
            'phone' => '081200000004',
        ]);
        app(DebtService::class)->createDebt(['customer_id' => $customer->id, 'amount' => 300000]);

        Livewire::actingAs($this->admin())
            ->test(CollectivePaymentPage::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'payment_date' => today()->toDateString(),
                'amount' => 300001,
            ])
            ->call('process');

        $this->assertDatabaseMissing('collective_payments', [
            'customer_id' => $customer->id,
        ]);

        $debt = $customer->debts()->firstOrFail();
        $this->assertEquals(0, (float) $debt->paid_amount);
        $this->assertEquals(300000, (float) $debt->remaining_amount);
    }

    public function test_customer_share_pdf_action_downloads_pdf(): void
    {
        $customer = Customer::has('debts')->firstOrFail();

        $component = Livewire::actingAs($this->admin())
            ->test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('share_pdf');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertStringStartsWith('Rincian-Hutang-', $download['name']);
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));
    }

    public function test_reports_export_actions_download_files(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->callAction('export_pdf');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));

        $component = Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->callAction('export_excel');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertStringStartsWith('Laporan-', $download['name']);
    }

    public function test_reports_page_generates_result(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->fillForm([
                'type' => ReportService::TYPE_DEBT_LIST,
            ])
            ->call('show')
            ->assertSet('results', fn (mixed $value) => $value !== null);
    }
}
