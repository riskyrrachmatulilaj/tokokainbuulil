<?php

namespace Tests\Feature;

use App\Filament\Pages\KasirPage;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaleDraftAndEditTest extends TestCase
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

    public function test_sale_draft_model_crud(): void
    {
        $user = $this->kasir();
        $product = Product::create(['name' => 'Kain Toyobo', 'price' => 30000, 'track_stock' => true, 'stock' => 50, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Bu Hani', 'phone' => '08123456789']);

        $draft = SaleDraft::create([
            'user_id' => $user->id,
            'reference_name' => 'Draft Pesanan Bu Hani',
            'cart_data' => [
                [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => 30000,
                    'quantity' => 3,
                    'notes' => 'Warna Navy',
                    'subtotal' => 90000,
                ]
            ],
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_RECEIVABLE,
            'sale_date' => today()->toDateString(),
            'total_amount' => 90000,
        ]);

        $this->assertDatabaseHas('sale_drafts', [
            'id' => $draft->id,
            'reference_name' => 'Draft Pesanan Bu Hani',
            'receivable_party_id' => $party->id,
        ]);

        $this->assertEquals(90000, (float) $draft->total_amount);
        $this->assertCount(1, $draft->cart_data);
        $this->assertEquals('Kain Toyobo', $draft->cart_data[0]['name']);
    }

    public function test_kasir_draft_actions(): void
    {
        $user = $this->kasir();
        $product = Product::create(['name' => 'Kain Rayon', 'price' => 25000, 'track_stock' => true, 'stock' => 100, 'is_active' => true]);

        $this->actingAs($user);

        $component = Livewire::test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->set('draftReferenceName', 'Draft 1 Rayon')
            ->call('saveDraft');

        $this->assertDatabaseHas('sale_drafts', [
            'reference_name' => 'Draft 1 Rayon',
            'user_id' => $user->id,
        ]);

        $draft = SaleDraft::where('reference_name', 'Draft 1 Rayon')->first();
        $this->assertNotNull($draft);

        $component->assertSet('cart', []);

        $component->call('loadDraft', $draft->id);
        $component->assertSet('cart.0.product_id', $product->id);
        $this->assertDatabaseMissing('sale_drafts', ['id' => $draft->id]);
    }

    public function test_update_sale_adjusts_stock_and_items(): void
    {
        $user = $this->admin();
        $party = ReceivableParty::create(['name' => 'Pelanggan Umum', 'phone' => '08123450000']);
        $productA = Product::create(['name' => 'Kain A', 'price' => 20000, 'track_stock' => true, 'stock' => 10, 'is_active' => true]);
        $productB = Product::create(['name' => 'Kain B', 'price' => 50000, 'track_stock' => true, 'stock' => 10, 'is_active' => true]);

        $saleService = app(SaleService::class);

        $sale = $saleService->createSale([
            'receivable_party_id' => $party->id,
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 4],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 100000,
            'sale_date' => today()->toDateString(),
        ], $user);

        $this->assertEquals(6, (float) $productA->fresh()->stock);

        $updatedSale = $saleService->updateSale($sale, [
            'receivable_party_id' => $party->id,
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2, 'unit_price' => 20000],
                ['product_id' => $productB->id, 'quantity' => 3, 'unit_price' => 50000],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 200000,
            'sale_date' => today()->toDateString(),
        ], $user);

        $this->assertEquals(8, (float) $productA->fresh()->stock);
        $this->assertEquals(7, (float) $productB->fresh()->stock);
        $this->assertEquals(2 * 20000 + 3 * 50000, (float) $updatedSale->total_amount);
        $this->assertEquals(200000, (float) $updatedSale->received_amount);
        $this->assertCount(2, $updatedSale->items);
    }

    public function test_update_sale_credit_to_receivable_sync(): void
    {
        $user = $this->admin();
        $product = Product::create(['name' => 'Kain Katun', 'price' => 100000, 'track_stock' => true, 'stock' => 20, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Toko Barokah', 'phone' => '0899999999']);

        $saleService = app(SaleService::class);

        // 1. Create credit sale
        $sale = $saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_RECEIVABLE,
            'receivable_party_id' => $party->id,
            'due_date' => today()->addDays(30)->toDateString(),
            'description' => 'Piutang awal',
            'sale_date' => today()->toDateString(),
        ], $user);

        $this->assertNotNull($sale->receivable_id);
        $receivable = Receivable::find($sale->receivable_id);
        $this->assertEquals(200000, (float) $receivable->amount);
        $this->assertEquals(200000, (float) $receivable->remaining_amount);

        // 2. Update credit sale: quantity increased to 3
        $updatedSale = $saleService->updateSale($sale, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100000],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_RECEIVABLE,
            'receivable_party_id' => $party->id,
            'due_date' => today()->addDays(45)->toDateString(),
            'description' => 'Piutang diupdate',
            'sale_date' => today()->toDateString(),
        ], $user);

        $receivable = $receivable->fresh();
        $this->assertEquals(300000, (float) $receivable->amount);
        $this->assertEquals(300000, (float) $receivable->remaining_amount);
    }

    public function test_credit_cash_sale_creates_receivable_and_dp_installment(): void
    {
        $user = $this->admin();
        $product = Product::create(['name' => 'Kain Sutra', 'price' => 100000, 'track_stock' => true, 'stock' => 10, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Hj. Fatimah', 'phone' => '08111222333']);

        $saleService = app(SaleService::class);

        // Total: 2 * 100000 = 200000, DP: 50000
        $sale = $saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CREDIT_CASH,
            'cash_amount' => 50000,
            'receivable_party_id' => $party->id,
            'due_date' => today()->addDays(30)->toDateString(),
            'sale_date' => today()->toDateString(),
        ], $user);

        $this->assertEquals(Sale::PAYMENT_METHOD_CREDIT_CASH, $sale->payment_method);
        $this->assertEquals(200000, (float) $sale->total_amount);
        $this->assertEquals(50000, (float) $sale->cash_amount);
        $this->assertEquals(50000, (float) $sale->down_payment);
        $this->assertEquals(150000, (float) $sale->remaining_credit);

        $this->assertNotNull($sale->receivable_id);
        $receivable = Receivable::with('installments')->find($sale->receivable_id);
        $this->assertEquals(200000, (float) $receivable->amount);
        $this->assertEquals(50000, (float) $receivable->paid_amount);
        $this->assertEquals(150000, (float) $receivable->remaining_amount);
        $this->assertEquals(Receivable::STATUS_UNPAID, $receivable->status);
        $this->assertCount(1, $receivable->installments);
        $this->assertEquals(50000, (float) $receivable->installments->first()->amount);
        $this->assertStringContainsString('Uang Muka Transaksi', $receivable->installments->first()->description);
        $this->assertStringContainsString('Tunai', $receivable->installments->first()->description);
    }

    public function test_credit_transfer_sale_via_kasir_page(): void
    {
        $user = $this->kasir();
        $product = Product::create(['name' => 'Kain Wolfis', 'price' => 50000, 'track_stock' => true, 'stock' => 20, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pak Joko', 'phone' => '08555666777']);

        $this->actingAs($user);

        $component = Livewire::test(KasirPage::class)
            ->call('addToCart', $product->id) // 1 x 50000
            ->set('paymentMethod', Sale::PAYMENT_METHOD_CREDIT_TRANSFER)
            ->set('transferAmount', 20000)
            ->set('receivablePartyId', $party->id)
            ->call('processSale');

        $result = $component->get('result');
        $this->assertNotNull($result);
        $this->assertEquals(50000, (float) $result['total']);
        $this->assertEquals(20000, (float) $result['down_payment']);
        $this->assertEquals(30000, (float) $result['remaining_credit']);

        $sale = Sale::where('transaction_number', $result['transaction_number'])->firstOrFail();
        $this->assertEquals(Sale::PAYMENT_METHOD_CREDIT_TRANSFER, $sale->payment_method);
        $this->assertEquals(20000, (float) $sale->transfer_amount);
        $this->assertEquals(30000, (float) $sale->remaining_credit);
    }
}