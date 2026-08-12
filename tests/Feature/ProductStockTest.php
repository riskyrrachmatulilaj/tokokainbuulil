<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ReceivableParty $party;
    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->party = ReceivableParty::create([
            'name' => 'Debitur Test',
            'phone' => '08123456789',
        ]);
        $this->saleService = app(SaleService::class);
    }

    public function test_untracked_product_allows_sale_without_stock_deduction(): void
    {
        $product = Product::create([
            'name' => 'Kain Kustom Meteran',
            'price' => 25000,
            'track_stock' => false,
            'stock' => null,
            'is_active' => true,
        ]);

        $sale = $this->saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 100],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $this->party->id,
            'received_amount' => 2500000,
        ], $this->admin);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertNull($product->fresh()->stock);
    }

    public function test_tracked_product_deducts_stock_upon_sale(): void
    {
        $product = Product::create([
            'name' => 'Kain Batik Roll',
            'price' => 50000,
            'track_stock' => true,
            'stock' => 10.0,
            'is_active' => true,
        ]);

        $sale = $this->saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3.5],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $this->party->id,
            'received_amount' => 200000,
        ], $this->admin);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertEquals(6.5, (float) $product->fresh()->stock);
    }

    public function test_tracked_product_rejects_sale_when_stock_insufficient(): void
    {
        $product = Product::create([
            'name' => 'Kain Limited',
            'price' => 100000,
            'track_stock' => true,
            'stock' => 2.0,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        $this->saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $this->party->id,
            'received_amount' => 500000,
        ], $this->admin);
    }

    public function test_deleting_sale_restores_tracked_stock(): void
    {
        $product = Product::create([
            'name' => 'Kain Sutra',
            'price' => 80000,
            'track_stock' => true,
            'stock' => 15.0,
            'is_active' => true,
        ]);

        $sale = $this->saleService->createSale([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $this->party->id,
            'received_amount' => 400000,
        ], $this->admin);

        $this->assertEquals(10.0, (float) $product->fresh()->stock);

        $this->saleService->deleteSale($sale);

        $this->assertEquals(15.0, (float) $product->fresh()->stock);
    }
}
