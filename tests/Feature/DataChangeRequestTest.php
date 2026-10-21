<?php

namespace Tests\Feature;

use App\Models\DataChangeRequest;
use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\DataChangeRequestService;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DataChangeRequestTest extends TestCase
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

    public function test_kasir_cannot_direct_update_sale_policy(): void
    {
        $admin = $this->admin();
        $kasir = $this->kasir();

        $product = Product::create(['name' => 'Kain Rayon Polos', 'price' => 25000, 'stock' => 100, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Bu Hani', 'phone' => '081234567890']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 50000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 25000],
            ],
        ], $kasir);

        $this->assertFalse($kasir->can('update', $sale));
        $this->assertFalse($kasir->can('delete', $sale));
        $this->assertTrue($admin->can('update', $sale));
        $this->assertTrue($admin->can('delete', $sale));
        $this->assertTrue($kasir->can('requestChange', $sale));
    }

    public function test_kasir_can_create_change_request_for_edit(): void
    {
        $kasir = $this->kasir();
        $product = Product::create(['name' => 'Kain Katun Jepang', 'price' => 35000, 'stock' => 50, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pak Joko', 'phone' => '081234567891']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 70000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 35000],
            ],
        ], $kasir);

        $service = app(DataChangeRequestService::class);
        $request = $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_EDIT,
            'Salah input jumlah kain, seharusnya 1 meter',
            [
                'sale_date' => now()->format('Y-m-d'),
                'receivable_party_id' => $party->id,
                'payment_method' => Sale::PAYMENT_METHOD_CASH,
                'received_amount' => 35000,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 35000],
                ],
            ]
        );

        $this->assertDatabaseHas('data_change_requests', [
            'id' => $request->id,
            'user_id' => $kasir->id,
            'request_type' => DataChangeRequest::TYPE_EDIT,
            'status' => DataChangeRequest::STATUS_PENDING,
        ]);

        $this->assertTrue($sale->fresh()->hasPendingChangeRequest());
    }

    public function test_cannot_create_duplicate_pending_request_for_same_sale(): void
    {
        $kasir = $this->kasir();
        $product = Product::create(['name' => 'Kain Sutra', 'price' => 100000, 'stock' => 10, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Ibu Rina', 'phone' => '081234567892']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 100000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 100000],
            ],
        ], $kasir);

        $service = app(DataChangeRequestService::class);
        $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_VOID,
            'Pelanggan batalkan pesanan'
        );

        $this->expectException(ValidationException::class);
        $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_VOID,
            'Pengajuan kedua yang seharusnya ditolak'
        );
    }

    public function test_admin_can_approve_void_request(): void
    {
        $admin = $this->admin();
        $kasir = $this->kasir();
        $product = Product::create(['name' => 'Kain Linen', 'price' => 50000, 'stock' => 20, 'track_stock' => true, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Ibu Siti', 'phone' => '081234567893']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 100000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50000],
            ],
        ], $kasir);

        // Stok awal 20 berkurang 2 menjadi 18
        $this->assertEquals(18, $product->fresh()->stock);

        $service = app(DataChangeRequestService::class);
        $request = $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_VOID,
            'Salah buat nota, pembeli tidak jadi beli'
        );

        $service->approveRequest($request, $admin);

        $this->assertEquals(DataChangeRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertEquals($admin->id, $request->fresh()->reviewed_by);
        $this->assertNotNull($request->fresh()->reviewed_at);

        // Nota terhapus dan stok dikembalikan menjadi 20
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertEquals(20, $product->fresh()->stock);
    }

    public function test_admin_can_approve_edit_request(): void
    {
        $admin = $this->admin();
        $kasir = $this->kasir();
        $product = Product::create(['name' => 'Kain Brokat', 'price' => 60000, 'stock' => 30, 'track_stock' => true, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Mbak Maya', 'phone' => '081234567894']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 180000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'price' => 60000],
            ],
        ], $kasir);

        // Stok awal 30 berkurang 3 = 27
        $this->assertEquals(27, $product->fresh()->stock);

        $service = app(DataChangeRequestService::class);
        $request = $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_EDIT,
            'Koreksi beli 1 meter saja',
            [
                'sale_date' => now()->format('Y-m-d'),
                'receivable_party_id' => $party->id,
                'payment_method' => Sale::PAYMENT_METHOD_CASH,
                'received_amount' => 60000,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'price' => 60000],
                ],
            ]
        );

        $service->approveRequest($request, $admin);

        $this->assertEquals(DataChangeRequest::STATUS_APPROVED, $request->fresh()->status);
        // Total nota berubah menjadi 60000
        $this->assertEquals(60000, (float) $sale->fresh()->total_amount);
        // Stok dikembalikan selisihnya: dari 3 meter jadi 1 meter, sisa stok harus 29
        $this->assertEquals(29, $product->fresh()->stock);
    }

    public function test_admin_can_reject_request_with_reason(): void
    {
        $admin = $this->admin();
        $kasir = $this->kasir();
        $product = Product::create(['name' => 'Kain Drill', 'price' => 40000, 'stock' => 15, 'is_active' => true]);
        $party = ReceivableParty::create(['name' => 'Pak Rudi', 'phone' => '081234567895']);

        $sale = app(SaleService::class)->createSale([
            'sale_date' => now()->format('Y-m-d'),
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => 40000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 40000],
            ],
        ], $kasir);

        $service = app(DataChangeRequestService::class);
        $request = $service->createSaleChangeRequest(
            $sale,
            $kasir,
            DataChangeRequest::TYPE_VOID,
            'Ingin batalkan tanpa konfirmasi pembeli'
        );

        $service->rejectRequest($request, $admin, 'Pembatalan ditolak karena barang sudah dibawa pembeli.');

        $this->assertEquals(DataChangeRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertEquals('Pembatalan ditolak karena barang sudah dibawa pembeli.', $request->fresh()->rejection_reason);
        $this->assertEquals($admin->id, $request->fresh()->reviewed_by);
        // Penjualan tetap ada di database
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }
}
