<?php

namespace Tests\Feature;

use App\Filament\Pages\ReceivableCollectivePaymentPage;
use App\Filament\Pages\ReceivableReports;
use App\Filament\Resources\ReceivablePartyResource\Pages\EditReceivableParty;
use App\Filament\Resources\ReceivablePartyResource\Pages\ViewReceivableParty;
use App\Filament\Resources\ReceivablePartyResource\RelationManagers\ReceivablesRelationManager;
use App\Filament\Resources\ReceivableResource\Pages\EditReceivable;
use App\Filament\Resources\ReceivableResource\RelationManagers\ReceivableInstallmentsRelationManager;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use App\Models\User;
use App\Services\ReceivablePaymentService;
use App\Services\ReceivableReportService;
use App\Services\ReceivableService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReceivableActionFlowsTest extends TestCase
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
        $receivable = Receivable::where('status', Receivable::STATUS_UNPAID)->firstOrFail();
        $before = (float) $receivable->remaining_amount;
        $amount = 10000;

        Livewire::actingAs($this->admin())
            ->test(ReceivableInstallmentsRelationManager::class, [
                'ownerRecord' => $receivable,
                'pageClass' => EditReceivable::class,
            ])
            ->callTableAction('create', data: [
                'amount' => $amount,
                'installment_date' => today()->toDateString(),
            ]);

        $this->assertDatabaseHas('receivable_installments', [
            'receivable_id' => $receivable->id,
            'amount' => $amount,
        ]);

        $this->assertDatabaseHas('receivable_payment_histories', [
            'receivable_id' => $receivable->id,
            'payment_type' => ReceivablePaymentHistory::TYPE_INSTALLMENT,
            'amount' => $amount,
        ]);

        $receivable->refresh();
        $this->assertEquals($before - $amount, (float) $receivable->remaining_amount);
    }

    public function test_installments_relation_manager_rejects_overpayment(): void
    {
        $party = ReceivableParty::create([
            'name' => 'Debitur Overpay Test',
            'phone' => '081200000001',
        ]);
        $receivable = app(ReceivableService::class)->createReceivable(['receivable_party_id' => $party->id, 'amount' => 300000]);
        $overpay = (float) $receivable->remaining_amount + 1;

        Livewire::actingAs($this->admin())
            ->test(ReceivableInstallmentsRelationManager::class, [
                'ownerRecord' => $receivable,
                'pageClass' => EditReceivable::class,
            ])
            ->callTableAction('create', data: [
                'amount' => $overpay,
                'installment_date' => today()->toDateString(),
            ])
            ->assertTableActionHalted('create');

        $this->assertDatabaseMissing('receivable_installments', [
            'receivable_id' => $receivable->id,
        ]);

        $receivable->refresh();
        $this->assertEquals((float) $receivable->amount, (float) $receivable->remaining_amount);
    }

    public function test_receivables_relation_manager_creates_receivable(): void
    {
        $party = ReceivableParty::create([
            'name' => 'Debitur Nota Test',
            'phone' => '081200000002',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ReceivablesRelationManager::class, [
                'ownerRecord' => $party,
                'pageClass' => EditReceivableParty::class,
            ])
            ->callTableAction('create', data: [
                'amount' => 350000,
                'receivable_date' => today()->toDateString(),
            ]);

        $this->assertDatabaseHas('receivables', [
            'receivable_party_id' => $party->id,
            'amount' => 350000,
            'paid_amount' => 0,
            'remaining_amount' => 350000,
            'status' => Receivable::STATUS_UNPAID,
        ]);

        $receivable = $party->receivables()->firstOrFail();
        $this->assertStringStartsWith('PINV-', $receivable->invoice_number);
    }

    public function test_cancel_installment_via_relation_manager_restores_balance(): void
    {
        $receivable = Receivable::where('status', Receivable::STATUS_UNPAID)->firstOrFail();
        $beforePaid = (float) $receivable->paid_amount;

        $installment = app(ReceivablePaymentService::class)->recordInstallment([
            'receivable_id' => $receivable->id,
            'amount' => 10000,
            'installment_date' => today()->toDateString(),
        ], $this->admin());

        $receivable->refresh();
        $this->assertEquals($beforePaid + 10000, (float) $receivable->paid_amount);

        Livewire::actingAs($this->admin())
            ->test(ReceivableInstallmentsRelationManager::class, [
                'ownerRecord' => $receivable,
                'pageClass' => EditReceivable::class,
            ])
            ->callTableAction('delete', $installment);

        $this->assertSoftDeleted('receivable_installments', ['id' => $installment->id]);

        $receivable->refresh();
        $this->assertEquals($beforePaid, (float) $receivable->paid_amount);
        $this->assertEquals((float) $receivable->amount - $beforePaid, (float) $receivable->remaining_amount);

        $this->assertDatabaseMissing('receivable_payment_histories', [
            'installment_id' => $installment->id,
        ]);
    }

    public function test_collective_payment_page_processes_fifo_allocation(): void
    {
        $party = ReceivableParty::create([
            'name' => 'Debitur Kolektif Test',
            'phone' => '081200000003',
        ]);
        $service = app(ReceivableService::class);
        $oldest = $service->createReceivable(['receivable_party_id' => $party->id, 'amount' => 400000, 'receivable_date' => today()->subDays(2)]);
        $newer = $service->createReceivable(['receivable_party_id' => $party->id, 'amount' => 600000, 'receivable_date' => today()->subDay()]);

        Livewire::actingAs($this->admin())
            ->test(ReceivableCollectivePaymentPage::class)
            ->fillForm([
                'receivable_party_id' => $party->id,
                'payment_date' => today()->toDateString(),
                'amount' => 500000,
                'description' => 'Terima sebagian dua nota',
            ])
            ->call('process');

        $oldest->refresh();
        $newer->refresh();

        $this->assertEquals(400000, (float) $oldest->paid_amount);
        $this->assertEquals(0, (float) $oldest->remaining_amount);
        $this->assertEquals(Receivable::STATUS_PAID, $oldest->status);

        $this->assertEquals(100000, (float) $newer->paid_amount);
        $this->assertEquals(500000, (float) $newer->remaining_amount);
        $this->assertEquals(Receivable::STATUS_UNPAID, $newer->status);

        $this->assertDatabaseHas('receivable_collective_payments', [
            'receivable_party_id' => $party->id,
            'amount' => 500000,
        ]);

        $this->assertDatabaseHas('receivable_payment_histories', [
            'receivable_party_id' => $party->id,
            'payment_type' => ReceivablePaymentHistory::TYPE_COLLECTIVE,
        ]);
    }

    public function test_collective_payment_page_rejects_amount_above_total_remaining(): void
    {
        $party = ReceivableParty::create([
            'name' => 'Debitur Kolektif Overpay',
            'phone' => '081200000004',
        ]);
        app(ReceivableService::class)->createReceivable(['receivable_party_id' => $party->id, 'amount' => 300000]);

        Livewire::actingAs($this->admin())
            ->test(ReceivableCollectivePaymentPage::class)
            ->fillForm([
                'receivable_party_id' => $party->id,
                'payment_date' => today()->toDateString(),
                'amount' => 300001,
            ])
            ->call('process');

        $this->assertDatabaseMissing('receivable_collective_payments', [
            'receivable_party_id' => $party->id,
        ]);

        $receivable = $party->receivables()->firstOrFail();
        $this->assertEquals(0, (float) $receivable->paid_amount);
        $this->assertEquals(300000, (float) $receivable->remaining_amount);
    }

    public function test_party_share_pdf_action_downloads_pdf(): void
    {
        $party = ReceivableParty::has('receivables')->firstOrFail();

        $component = Livewire::actingAs($this->admin())
            ->test(ViewReceivableParty::class, ['record' => $party->getRouteKey()])
            ->callAction('share_pdf');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertStringStartsWith('Rincian-Piutang-', $download['name']);
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));
    }

    public function test_reports_export_actions_download_files(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ReceivableReports::class)
            ->callAction('export_pdf');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));

        $component = Livewire::actingAs($this->admin())
            ->test(ReceivableReports::class)
            ->callAction('export_excel');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertStringStartsWith('Laporan-', $download['name']);
    }

    public function test_reports_page_generates_result(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ReceivableReports::class)
            ->fillForm([
                'type' => ReceivableReportService::TYPE_RECEIVABLE_LIST,
            ])
            ->call('show')
            ->assertSet('results', fn (mixed $value) => $value !== null);
    }

    public function test_associate_and_update_receivable_party_relationship(): void
    {
        $receivable = Receivable::firstOrFail();
        $newParty = ReceivableParty::create([
            'name' => 'Debitur Baru Test',
            'phone' => '08123456789',
        ]);

        $receivable->party()->associate($newParty);
        $receivable->save();

        $this->assertEquals($newParty->id, $receivable->fresh()->receivable_party_id);
    }
}
