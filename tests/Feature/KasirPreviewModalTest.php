<?php

namespace Tests\Feature;

use App\Filament\Pages\KasirPage;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KasirPreviewModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cannot_open_preview_modal_if_cart_is_empty(): void
    {
        $user = User::firstOrFail();

        Livewire::actingAs($user)
            ->test(KasirPage::class)
            ->call('openPreviewModal')
            ->assertSet('showPreviewModal', false)
            ->assertNotified();
    }

    public function test_can_open_and_close_preview_modal_with_cart_items(): void
    {
        $user = User::firstOrFail();
        $product = Product::firstOrFail();

        Livewire::actingAs($user)
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->assertSet('showPreviewModal', false)
            ->call('openPreviewModal')
            ->assertSet('showPreviewModal', true)
            ->call('closePreviewModal')
            ->assertSet('showPreviewModal', false);
    }

    public function test_print_draft_nota_dispatches_event(): void
    {
        $user = User::firstOrFail();
        $product = Product::firstOrFail();

        Livewire::actingAs($user)
            ->test(KasirPage::class)
            ->call('addToCart', $product->id)
            ->call('openPreviewModal')
            ->call('printDraftNota')
            ->assertDispatched('do-print-draft-nota');
    }
}
