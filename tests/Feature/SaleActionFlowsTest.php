<?php

namespace Tests\Feature;

use App\Filament\Pages\DailySalesReport;
use App\Filament\Pages\KasirPage;
use App\Filament\Resources\SaleResource\Pages\ViewSale;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleActionFlowsTest extends TestCase
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

    public function test_kasir_page_adds_increments_and_removes_cart_items(): void
    {
        $product = Product::create(['name' => 'Kain Kasir', 'price' => 30000, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->assertSet('cart', [])
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->assertSet('cart.0.quantity', 2)
            ->assertSet('cart.0.subtotal', 60000)
            ->call('incrementQty', 0)
            ->assertSet('cart.0.quantity', 3)
            ->assertSet('cart.0.subtotal', 90000)
            ->call('decrementQty', 0)
            ->assertSet('cart.0.quantity', 2)
            ->assertSet('cart.0.subtotal', 60000)
            ->call('setQty', 0, 5)
            ->assertSet('cart.0.quantity', 5)
            ->assertSet('cart.0.subtotal', 150000)
            ->call('setQty', 0, 0)
            ->assertSet('cart', []);
    }

    public function test_kasir_page_processes_cash_sale(): void
    {
        $product = Product::create(['name' => 'Kain Kasir Tunai', 'price' => 45000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Kasir Tunai', 'phone' => '081300000020']);

        $component = Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->set('receivablePartyId', $party->id)
            ->set('receivedAmount', 100000)
            ->call('processSale');

        $component->assertSet('result.transaction_number', fn (mixed $value) => str_starts_with((string) $value, 'SLS-'));
        $component->assertSet('result.change', 10000);
        $component->assertSet('result.party_name', 'Pelanggan Kasir Tunai');
        $component->assertSet('cart', []);

        $this->assertDatabaseHas('sales', [
            'total_amount' => 90000,
            'received_amount' => 100000,
            'change_amount' => 10000,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
        ]);
    }

    public function test_kasir_page_rejects_cash_sale_without_customer(): void
    {
        $product = Product::create(['name' => 'Kain Kasir Tanpa Pelanggan', 'price' => 30000, 'is_active' => true]);
        $salesBefore = Sale::count();

        Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->set('receivedAmount', 30000)
            ->call('processSale');

        $this->assertEquals($salesBefore, Sale::count());
    }

    public function test_kasir_page_processes_receivable_sale(): void
    {
        $product = Product::create(['name' => 'Kain Kasir Kredit', 'price' => 55000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pembeli Kasir', 'phone' => '081300000002']);

        $component = Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', Sale::PAYMENT_METHOD_RECEIVABLE)
            ->set('receivablePartyId', $party->id)
            ->call('processSale');

        $component->assertSet('result.payment_method', Sale::PAYMENT_METHOD_RECEIVABLE);
        $component->assertSet('result.receivable_invoice', fn (mixed $value) => str_starts_with((string) $value, 'PINV-'));

        $sale = Sale::where('payment_method', Sale::PAYMENT_METHOD_RECEIVABLE)->latest('id')->firstOrFail();
        $this->assertEquals($party->id, $sale->receivable_party_id);
        $this->assertNotNull($sale->receivable_id);

        $this->assertDatabaseHas('receivables', [
            'id' => $sale->receivable_id,
            'receivable_party_id' => $party->id,
            'status' => Receivable::STATUS_UNPAID,
        ]);
    }

    public function test_kasir_page_rejects_insufficient_cash(): void
    {
        $product = Product::create(['name' => 'Kain Kasir', 'price' => 30000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Kurang', 'phone' => '081300000021']);
        $salesBefore = Sale::count();

        Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->set('receivablePartyId', $party->id)
            ->set('receivedAmount', 10000)
            ->call('processSale');

        $this->assertEquals($salesBefore, Sale::count());
    }

    public function test_kasir_page_rejects_empty_cart(): void
    {
        $salesBefore = Sale::count();

        Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->set('receivedAmount', 10000)
            ->call('processSale');

        $this->assertEquals($salesBefore, Sale::count());
    }

    public function test_kasir_page_print_nota_downloads_pdf(): void
    {
        $product = Product::create(['name' => 'Kain Kasir Nota', 'price' => 20000, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Nota', 'phone' => '081300000022']);

        $component = Livewire::actingAs($this->admin())
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->set('receivablePartyId', $party->id)
            ->set('receivedAmount', 20000)
            ->call('processSale')
            ->call('printNota');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));
    }

    public function test_sale_view_page_print_nota_action_downloads_pdf(): void
    {
        $sale = Sale::firstOrFail();

        $component = Livewire::actingAs($this->admin())
            ->test(ViewSale::class, ['record' => $sale->getRouteKey()])
            ->callAction('print_nota');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a file download effect.');
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));
    }

    public function test_daily_sales_report_page_generates_result(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DailySalesReport::class)
            ->call('show')
            ->assertSet('report', fn (mixed $value) => is_array($value) && ($value['summary']['transactions'] ?? 0) >= 1);
    }

    public function test_daily_sales_report_export_actions_download_files(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(DailySalesReport::class)
            ->callAction('export_pdf');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected a PDF download effect.');
        $this->assertEquals('application/pdf', $download['contentType']);
        $this->assertStringStartsWith('%PDF-', base64_decode($download['content']));

        $component = Livewire::actingAs($this->admin())
            ->test(DailySalesReport::class)
            ->callAction('export_excel');

        $download = data_get($component->effects, 'download');
        $this->assertNotNull($download, 'Expected an Excel download effect.');
        $this->assertStringStartsWith('Laporan-Penjualan-Harian-', $download['name']);
    }

    public function test_kasir_page_filters_parties_by_search_query(): void
    {
        ReceivableParty::query()->delete();

        $party1 = ReceivableParty::create(['name' => 'Alice Margatroid', 'phone' => '081234567890']);
        $party2 = ReceivableParty::create(['name' => 'Marisa Kirisame', 'phone' => '087654321098']);

        $component = Livewire::actingAs($this->admin())
            ->test(KasirPage::class);

        $parties = $component->instance()->parties();
        $this->assertCount(2, $parties);

        $component->set('partySearch', 'Alice');
        $parties = $component->instance()->parties();
        $this->assertCount(1, $parties);
        $this->assertEquals('Alice Margatroid', $parties->first()->name);

        $component->set('partySearch', '08765');
        $parties = $component->instance()->parties();
        $this->assertCount(1, $parties);
        $this->assertEquals('Marisa Kirisame', $parties->first()->name);

        $component->set('partySearch', 'Reimu');
        $parties = $component->instance()->parties();
        $this->assertCount(0, $parties);

        // Test selecting a party
        $component->call('selectParty', $party1->id);
        $this->assertEquals($party1->id, $component->get('receivablePartyId'));
        $this->assertEquals('Alice Margatroid', $component->get('partySearch'));

        // Test changing the search input clears the selection
        $component->set('partySearch', 'Alice Marga');
        $this->assertNull($component->get('receivablePartyId'));

        // Re-select and test clearing
        $component->call('selectParty', $party1->id);
        $component->call('clearSelectedParty');
        $this->assertNull($component->get('receivablePartyId'));
        $this->assertEquals('', $component->get('partySearch'));
    }
}

