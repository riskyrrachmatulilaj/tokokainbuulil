<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalePrintLayoutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_print_layout_endpoints_return_successful_pdf(): void
    {
        $user = User::firstOrFail();
        $sale = Sale::firstOrFail();

        $endpoints = [
            "/sales/{$sale->id}/nota",
            "/sales/{$sale->id}/thermal",
            "/sales/{$sale->id}/thermal?layout=compact",
            "/sales/{$sale->id}/thermal?layout=detail",
            "/sales/{$sale->id}/thermal?layout=roll",
            "/sales/{$sale->id}/continuous",
            "/sales/{$sale->id}/continuous-detail",
            "/sales/{$sale->id}/thermal-roll",
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertSuccessful();
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        }
    }
}
