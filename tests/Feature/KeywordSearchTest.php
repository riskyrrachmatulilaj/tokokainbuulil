<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KeywordSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_product_scope_search_matches_multi_word_keywords(): void
    {
        $product = Product::create([
            'name' => 'HDP GSM 1400 - Ukuran 100',
            'price' => 150000,
            'is_active' => true,
        ]);

        $otherProduct = Product::create([
            'name' => 'HDP GSM 1200 - Ukuran 50',
            'price' => 120000,
            'is_active' => true,
        ]);

        // Searching "1400 100" should return the target product and not the other
        $results = Product::search('1400 100')->get();
        $this->assertTrue($results->contains($product));
        $this->assertFalse($results->contains($otherProduct));

        // Searching in different order "100 1400" should also work
        $resultsReversed = Product::search('100 1400')->get();
        $this->assertTrue($resultsReversed->contains($product));
        $this->assertFalse($resultsReversed->contains($otherProduct));

        // Searching "GSM 1400" should match
        $resultsGsm = Product::search('GSM 1400')->get();
        $this->assertTrue($resultsGsm->contains($product));
        $this->assertFalse($resultsGsm->contains($otherProduct));

        // Searching "1400 999" should return nothing
        $noResults = Product::search('1400 999')->get();
        $this->assertEmpty($noResults);
    }

    public function test_kasir_page_product_search_with_keywords(): void
    {
        $product = Product::create([
            'name' => 'HDP GSM 1400 - Ukuran 100',
            'price' => 150000,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(\App\Filament\Pages\KasirPage::class)
            ->set('search', '1400 100')
            ->assertSee('HDP GSM 1400 - Ukuran 100');
    }

    public function test_customer_and_receivable_party_scope_search_with_multi_keywords(): void
    {
        $customer = Customer::create([
            'name' => 'Toko Kain Makmur',
            'phone' => '08123456789',
            'address' => 'Jl. Pasar Baru Solo',
        ]);

        $results = Customer::search('Makmur 0812')->get();
        $this->assertTrue($results->contains($customer));

        $party = ReceivableParty::create([
            'name' => 'Haji Ahmad Sutrisno',
            'phone' => '085712345678',
            'address' => 'Semarang Barat',
        ]);

        $partyResults = ReceivableParty::search('Ahmad Semarang')->get();
        $this->assertTrue($partyResults->contains($party));
    }
}
