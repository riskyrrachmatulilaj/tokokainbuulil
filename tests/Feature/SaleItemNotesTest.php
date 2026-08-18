<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleItemNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sale_item_saves_and_prints_notes_successfully(): void
    {
        $admin = User::firstOrFail();
        $product = Product::firstOrFail();
        $party = ReceivableParty::firstOrFail();

        $noteText = 'kain 1 53m dan kain 54m total 107m 2 roll';

        $sale = app(SaleService::class)->createSale([
            'receivable_party_id' => $party->id,
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'received_amount' => (float)$product->price * 2,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => (float)$product->price,
                    'notes' => $noteText,
                ],
            ],
        ], $admin);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'notes' => $noteText,
        ]);

        // WhatsApp message contains the note
        $this->assertStringContainsString($noteText, $sale->whatsapp_message_text);

        // All 4 print layouts generate without error
        $compactPdf = SaleThermalService::continuousCompactInline($sale);
        $this->assertEquals('application/pdf', $compactPdf->headers->get('Content-Type'));

        $detailPdf = SaleThermalService::continuousDetailInline($sale);
        $this->assertEquals('application/pdf', $detailPdf->headers->get('Content-Type'));

        $rollPdf = SaleThermalService::thermalRollInline($sale);
        $this->assertEquals('application/pdf', $rollPdf->headers->get('Content-Type'));

        $a4Pdf = SalePdfService::notaInline($sale);
        $this->assertEquals('application/pdf', $a4Pdf->headers->get('Content-Type'));
    }
}
