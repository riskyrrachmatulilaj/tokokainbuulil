<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Services\WhatsApp\WhatsAppBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppBotTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_command_returns_help_instructions(): void
    {
        $botService = app(WhatsAppBotService::class);
        $reply = $botService->handleIncoming('08123456789', 'menu');

        $this->assertStringContainsString('BOT TOKO KAIN BU ULIL', $reply);
        $this->assertStringContainsString('hutang [nama]', $reply);
        $this->assertStringContainsString('penjualan [waktu]', $reply);
    }

    public function test_hutang_pelanggan_query(): void
    {
        $party = ReceivableParty::create([
            'name' => 'Budi Santoso',
            'phone' => '081299998888',
        ]);

        Receivable::create([
            'invoice_number' => 'INV-TEST-001',
            'receivable_party_id' => $party->id,
            'amount' => 500000,
            'paid_amount' => 200000,
            'remaining_amount' => 300000,
            'receivable_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'status' => Receivable::STATUS_UNPAID,
        ]);

        $botService = app(WhatsAppBotService::class);
        $reply = $botService->handleIncoming('08123456789', 'hutang budi');

        $this->assertStringContainsString('Budi Santoso', $reply);
        $this->assertStringContainsString('INV-TEST-001', $reply);
        $this->assertStringContainsString('Rp 300.000', $reply);
    }

    public function test_penjualan_today_query(): void
    {
        Sale::create([
            'transaction_number' => 'TRX-TEST-01',
            'sale_date' => Carbon::today(),
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'total_amount' => 250000,
            'cash_amount' => 250000,
            'transfer_amount' => 0,
            'received_amount' => 250000,
            'change_amount' => 0,
        ]);

        $botService = app(WhatsAppBotService::class);
        $reply = $botService->handleIncoming('08123456789', 'penjualan hari ini');

        $this->assertStringContainsString('LAPORAN PENJUALAN TOKO', $reply);
        $this->assertStringContainsString('1 transaksi', $reply);
        $this->assertStringContainsString('Rp 250.000', $reply);
    }

    public function test_stok_product_query(): void
    {
        Product::create([
            'name' => 'Kain Katun Rayon Premium',
            'price' => 45000,
            'track_stock' => true,
            'stock' => 120,
            'is_active' => true,
        ]);

        $botService = app(WhatsAppBotService::class);
        $reply = $botService->handleIncoming('08123456789', 'stok rayon');

        $this->assertStringContainsString('Kain Katun Rayon Premium', $reply);
        $this->assertStringContainsString('Rp 45.000', $reply);
        $this->assertStringContainsString('Stok: 120', $reply);
    }

    public function test_authorization_filter(): void
    {
        config(['whatsapp.authorized_numbers' => ['08123456789']]);

        $botService = app(WhatsAppBotService::class);

        // Unauthorized sender
        $unauthReply = $botService->handleIncoming('08999999999', 'hutang budi');
        $this->assertStringContainsString('Akses ditolak', $unauthReply);

        // Authorized sender with international prefix (628123456789)
        $authReply = $botService->handleIncoming('628123456789', 'menu');
        $this->assertStringContainsString('BOT TOKO KAIN BU ULIL', $authReply);
    }

    public function test_webhook_endpoint_receives_and_responds(): void
    {
        config(['whatsapp.authorized_numbers' => []]);

        $response = $this->postJson('/api/webhook/whatsapp', [
            'sender' => '08123456789',
            'message' => 'menu',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'sender',
            'reply',
        ]);
        $response->assertJsonPath('status', 'success');
        $this->assertStringContainsString('BOT TOKO KAIN BU ULIL', $response->json('reply'));
    }

    public function test_webhook_secret_validation(): void
    {
        config(['whatsapp.webhook_secret' => 'super-secret-key']);

        // Without secret header
        $forbidden = $this->postJson('/api/webhook/whatsapp', [
            'sender' => '08123456789',
            'message' => 'menu',
        ]);
        $forbidden->assertStatus(403);

        // With valid secret header
        $valid = $this->postJson('/api/webhook/whatsapp', [
            'sender' => '08123456789',
            'message' => 'menu',
        ], [
            'X-Webhook-Secret' => 'super-secret-key',
        ]);
        $valid->assertStatus(200);
    }
}
