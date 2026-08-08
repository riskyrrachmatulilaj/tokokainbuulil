<?php

namespace Tests\Feature;

use App\Exports\PartyImportTemplateExport;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\ReceivablePartyResource\Pages\ListReceivableParties;
use App\Models\Customer;
use App\Models\ReceivableParty;
use App\Models\User;
use App\Services\PartyImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class PartyImportTest extends TestCase
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

    protected function writeXlsx(string $path, array $rows): void
    {
        $writer = new XlsxWriter();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();
    }

    protected function writeCsv(string $path, array $rows): void
    {
        $writer = new CsvWriter();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();
    }

    public function test_imports_new_customers_from_xlsx(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cust_import_').'.xlsx';
        $this->writeXlsx($path, [
            ['Nama', 'Telepon', 'Alamat'],
            ['Import Test Satu', '081111111111', 'Alamat Satu'],
            ['Import Test Dua', '081222222222', 'Alamat Dua'],
        ]);

        $result = app(PartyImportService::class)->import($path, Customer::class, 'xlsx');

        $this->assertSame(2, $result->created);
        $this->assertSame(0, $result->updated);
        $this->assertSame(0, $result->failuresCount);
        $this->assertDatabaseHas('customers', [
            'name' => 'Import Test Satu',
            'phone' => '081111111111',
            'address' => 'Alamat Satu',
        ]);

        @unlink($path);
    }

    public function test_upserts_customer_by_name_and_phone(): void
    {
        Customer::create([
            'name' => 'Upsert Target',
            'phone' => '081333333333',
            'address' => 'Lama',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'cust_upsert_').'.xlsx';
        $this->writeXlsx($path, [
            ['Nama', 'Telepon', 'Alamat'],
            ['Upsert Target', '081333333333', 'Alamat Baru'],
        ]);

        $result = app(PartyImportService::class)->import($path, Customer::class, 'xlsx');

        $this->assertSame(0, $result->created);
        $this->assertSame(1, $result->updated);
        $this->assertDatabaseHas('customers', [
            'name' => 'Upsert Target',
            'phone' => '081333333333',
            'address' => 'Alamat Baru',
        ]);
        $this->assertSame(1, Customer::where('name', 'Upsert Target')->count());

        @unlink($path);
    }

    public function test_validation_failures_are_reported_per_row(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cust_fail_').'.csv';
        $this->writeCsv($path, [
            ['Nama', 'Telepon', 'Alamat'],
            ['', '081444444444', 'Tanpa nama'],
            ['Valid Row', '081555555555', 'OK'],
            ['Valid Row', '081555555555', 'Duplikat berkas'],
        ]);

        $result = app(PartyImportService::class)->import($path, Customer::class, 'csv');

        $this->assertSame(1, $result->created);
        $this->assertSame(2, $result->failuresCount);
        $this->assertSame('Nama wajib diisi', $result->failures[0]['reason']);
        $this->assertStringContainsString('Duplikat dalam berkas', $result->failures[1]['reason']);

        @unlink($path);
    }

    public function test_imports_receivable_parties_as_debitur(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'party_import_').'.xlsx';
        $this->writeXlsx($path, [
            ['Nama', 'Telepon', 'Alamat'],
            ['Debitur Import A', '081666666666', 'Bandung'],
        ]);

        $result = app(PartyImportService::class)->import($path, ReceivableParty::class, 'xlsx');

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('receivable_parties', [
            'name' => 'Debitur Import A',
            'phone' => '081666666666',
        ]);

        @unlink($path);
    }

    public function test_rejects_missing_nama_column(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad_header_').'.xlsx';
        $this->writeXlsx($path, [
            ['Telepon', 'Alamat'],
            ['081777777777', 'X'],
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(PartyImportService::class)->import($path, Customer::class, 'xlsx');
        } finally {
            @unlink($path);
        }
    }

    public function test_template_download_returns_xlsx(): void
    {
        $response = PartyImportTemplateExport::download(PartyImportTemplateExport::TYPE_PELANGGAN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'template-import-pelanggan.xlsx',
            $response->headers->get('content-disposition')
        );

        $debitur = PartyImportTemplateExport::download(PartyImportTemplateExport::TYPE_DEBITUR);
        $this->assertStringContainsString(
            'template-import-debitur.xlsx',
            $debitur->headers->get('content-disposition')
        );
    }

    public function test_admin_sees_import_actions_on_list_pages(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListCustomers::class)
            ->assertActionVisible('import_parties')
            ->assertActionVisible('import_help');

        Livewire::actingAs($this->admin())
            ->test(ListReceivableParties::class)
            ->assertActionVisible('import_parties')
            ->assertActionVisible('import_help');
    }

    public function test_kasir_cannot_see_import_actions(): void
    {
        Livewire::actingAs($this->kasir())
            ->test(ListCustomers::class)
            ->assertActionHidden('import_parties')
            ->assertActionHidden('import_help');

        Livewire::actingAs($this->kasir())
            ->test(ListReceivableParties::class)
            ->assertActionHidden('import_parties')
            ->assertActionHidden('import_help');
    }

    public function test_admin_can_download_template_from_help_action(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListCustomers::class)
            ->callAction('import_help')
            ->assertSuccessful();
    }
}
