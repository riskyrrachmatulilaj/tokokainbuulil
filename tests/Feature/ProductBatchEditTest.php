<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductBatchEditTest extends TestCase
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

    public function test_batch_edit_fixed_price_and_status(): void
    {
        $p1 = Product::create(['name' => 'Batch Test A', 'price' => 50000, 'is_active' => true]);
        $p2 = Product::create(['name' => 'Batch Test B', 'price' => 60000, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableBulkAction('batch_edit', [$p1, $p2], data: [
                'price_mode' => 'fixed',
                'price_value' => 75000,
                'status_mode' => 'deactivate',
                'description_mode' => 'overwrite',
                'description_value' => 'Promo Batch',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $p1->id,
            'price' => 75000.00,
            'is_active' => false,
            'description' => 'Promo Batch',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $p2->id,
            'price' => 75000.00,
            'is_active' => false,
            'description' => 'Promo Batch',
        ]);
    }

    public function test_batch_edit_percentage_increase(): void
    {
        $p1 = Product::create(['name' => 'Persen Test A', 'price' => 100000, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableBulkAction('batch_edit', [$p1], data: [
                'price_mode' => 'percentage_increase',
                'price_value' => 10, // +10%
                'status_mode' => 'no_change',
                'description_mode' => 'no_change',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $p1->id,
            'price' => 110000.00,
        ]);
    }

    public function test_bulk_activate_and_deactivate(): void
    {
        $p1 = Product::create(['name' => 'Toggle Test A', 'price' => 10000, 'is_active' => false]);
        $p2 = Product::create(['name' => 'Toggle Test B', 'price' => 20000, 'is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableBulkAction('bulk_activate', [$p1, $p2]);

        $this->assertTrue($p1->fresh()->is_active);
        $this->assertTrue($p2->fresh()->is_active);

        Livewire::actingAs($this->admin())
            ->test(ListProducts::class)
            ->callTableBulkAction('bulk_deactivate', [$p1, $p2]);

        $this->assertFalse($p1->fresh()->is_active);
        $this->assertFalse($p2->fresh()->is_active);
    }
}
