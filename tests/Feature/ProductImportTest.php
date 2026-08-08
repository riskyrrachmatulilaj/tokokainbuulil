<?php

namespace Tests\Feature;

use App\Exports\ProductImportTemplateExport;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class ProductImportTest extends TestCase
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

    public function test_imports_new_products_from_xlsx(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'prod_import_').'.xlsx';
        $this->writeXlsx($path, [
            ['Nama', 'Harga', 'Keterangan', 'Status'],
            ['Kain Import A', '95000', 'Katun import jepang', 'Aktif'],
            ['Kain Import B', '125000', 'Sutra satin', 'Nonaktif'],
        ]);

        $result = app(ProductImportService::class)->import($path, 'xlsx');

        $this->assertSame(2, $result->created);
        $this->assertSame(0, $result->updated);
        $this->assertSame(0, $result->failuresCount);
        
        $this->assertDatabaseHas('products', [
            'name' => 'Kain Import A',
            'price' => 95000.00,
            'description' => 'Katun import jepang',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Kain Import B',
            'price' => 125000.00,
            'description' => 'Sutra satin',
            'is_active' => false,
        ]);

        @unlink($path);
    }

    public function test_upserts_product_by_name(): void
    {
        Product::create([
            'name' => 'Kain Katun Lama',
            'price' => 40000,
            'description' => 'Keterangan lama',
            'is_active' => true,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'prod_upsert_').'.xlsx';
        $this->writeXlsx($path, [
            ['Nama', 'Harga', 'Keterangan', 'Status'],
            ['Kain Katun Lama', '45000', 'Keterangan baru', 'Nonaktif'],
        ]);

        $result = app(ProductImportService::class)->import($path, 'xlsx');

        $this->assertSame(0, $result->created);
        $this->assertSame(1, $result->updated);
        
        $this->assertDatabaseHas('products', [
            'name' => 'Kain Katun Lama',
            'price' => 45000.00,
            'description' => 'Keterangan baru',
            'is_active' => false,
        ]);
        
        $this->assertSame(1, Product::where('name', 'Kain Katun Lama')->count());

        @unlink($path);
    }

    public function test_validation_failures_are_reported_per_row(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'prod_fail_').'.csv';
        $this->writeCsv($path, [
            ['Nama', 'Harga', 'Keterangan', 'Status'],
            ['', '30000', 'Tanpa nama', 'Aktif'],
            ['Valid Row', '0', 'Harga nol', 'Aktif'],
            ['Valid Row', '50000', 'OK', 'Aktif'],
            ['Valid Row', '60000', 'Duplikat', 'Aktif'],
        ]);

        $result = app(ProductImportService::class)->import($path, 'csv');

        $this->assertSame(1, $result->created);
        $this->assertSame(3, $result->failuresCount);
        $this->assertSame('Nama Produk wajib diisi', $result->failures[0]['reason']);
        $this->assertSame('Harga Jual wajib berupa angka lebih dari 0', $result->failures[1]['reason']);
        $this->assertStringContainsString('Duplikat dalam berkas', $result->failures[2]['reason']);

        @unlink($path);
    }

    public function test_rejects_missing_nama_or_harga_column(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad_header_').'.xlsx';
        $this->writeXlsx($path, [
            ['Harga', 'Keterangan'],
            ['40000', 'X'],
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(ProductImportService::class)->import($path, 'xlsx');
        } finally {
            @unlink($path);
        }
    }

    public function test_template_download_returns_xlsx(): void
    {
        $response = ProductImportTemplateExport::download();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'template-import-produk.xlsx',
            $response->headers->get('content-disposition')
        );
    }

    public function test_admin_sees_import_actions_on_list_page(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->assertActionVisible('import_products')
            ->assertActionVisible('import_help');
    }

    public function test_kasir_cannot_see_import_actions(): void
    {
        Livewire::actingAs($this->kasir())
            ->test(ListProducts::class)
            ->assertActionHidden('import_products')
            ->assertActionHidden('import_help');
    }
}
