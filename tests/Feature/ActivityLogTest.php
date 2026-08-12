<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\DebtService;
use App\Services\PaymentService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_log_when_cash_sale_processed(): void
    {
        $user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasirtest@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_KASIR,
        ]);
        $party = ReceivableParty::create(['name' => 'Pelanggan Test']);
        $product = Product::create([
            'name' => 'Kain Test',
            'price' => 10000,
            'selling_price' => 10000,
            'is_active' => true,
        ]);

        $sale = app(SaleService::class)->createSale([
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $party->id,
            'received_amount' => 10000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'Penjualan',
            'action' => 'create',
            'subject_id' => $sale->id,
        ]);
    }

    public function test_creates_log_when_debt_installment_recorded(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admintest@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $customer = Customer::create(['name' => 'Supplier Test']);

        $debt = app(DebtService::class)->createDebt([
            'customer_id' => $customer->id,
            'amount' => 50000,
        ], $user);

        $installment = app(PaymentService::class)->recordInstallment([
            'debt_id' => $debt->id,
            'amount' => 20000,
        ], $user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'Hutang',
            'action' => 'pay',
            'subject_id' => $installment->id,
        ]);
    }

    public function test_creates_log_on_product_creation_and_update(): void
    {
        $product = Product::create([
            'name' => 'Kain Katun Premium',
            'price' => 20000,
            'selling_price' => 20000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Produk',
            'action' => 'create',
            'subject_id' => $product->id,
        ]);

        $product->update(['price' => 25000, 'name' => 'Kain Katun Premium Edited']);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Produk',
            'action' => 'update',
            'subject_id' => $product->id,
        ]);
    }
}
