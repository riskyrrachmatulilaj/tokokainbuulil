<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerDisplayTest extends TestCase
{
    public function test_customer_display_page_is_accessible(): void
    {
        $response = $this->get('/customer-display');
        $response->assertStatus(200);
        $response->assertSee('TOKO KAIN BU ULIL');
        $response->assertSee('TOTAL NOMINAL BELANJA');
    }

    public function test_customer_display_alias_page_is_accessible(): void
    {
        $response = $this->get('/display');
        $response->assertStatus(200);
    }

    public function test_customer_display_state_and_update(): void
    {
        // 1. Initial State
        $response = $this->get('/api/customer-display/state');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'cart', 'total_amount']);

        // 2. Update State
        $updateResponse = $this->postJson('/api/customer-display/update', [
            'status' => 'active',
            'cart' => [
                [
                    'product_id' => 1,
                    'name' => 'Kain Toyobo',
                    'price' => 35000,
                    'quantity' => 2,
                    'subtotal' => 70000,
                ],
            ],
            'total_amount' => 70000,
            'items_count' => 2,
            'payment_method' => 'cash',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);

        // 3. Verify Updated State
        $stateResponse = $this->get('/api/customer-display/state');
        $stateResponse->assertStatus(200);
        $stateResponse->assertJson([
            'status' => 'active',
            'total_amount' => 70000,
            'items_count' => 2,
        ]);
        $stateResponse->assertJsonPath('cart.0.name', 'Kain Toyobo');
    }

    public function test_customer_display_page_contains_items_containers_for_both_views(): void
    {
        $response = $this->get('/customer-display');
        $response->assertStatus(200);
        $response->assertSee('id="itemsContainer"', false);
        $response->assertSee('id="successItemsContainer"', false);
        $response->assertSee('Daftar Barang', false);
        $response->assertSee('Daftar Barang Belanjaan', false);
    }

    public function test_customer_display_success_state_maintains_cart_items(): void
    {
        $updateResponse = $this->postJson('/api/customer-display/update', [
            'status' => 'success',
            'cart' => [
                [
                    'product_id' => 10,
                    'name' => 'Kain Sprei Katun Jepang',
                    'price' => 50000,
                    'quantity' => 3,
                    'subtotal' => 150000,
                ],
            ],
            'total_amount' => 150000,
            'items_count' => 3,
            'payment_method' => 'cash',
            'received_amount' => 200000,
            'change_amount' => 50000,
            'transaction_number' => 'SLS-202609-0001',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);

        $stateResponse = $this->get('/api/customer-display/state');
        $stateResponse->assertStatus(200);
        $stateResponse->assertJson([
            'status' => 'success',
            'total_amount' => 150000,
            'transaction_number' => 'SLS-202609-0001',
        ]);
        $stateResponse->assertJsonPath('cart.0.name', 'Kain Sprei Katun Jepang');
        $stateResponse->assertJsonPath('cart.0.quantity', 3);
        $stateResponse->assertJsonPath('cart.0.subtotal', 150000);
    }
}
