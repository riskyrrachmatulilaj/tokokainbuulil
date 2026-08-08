<?php

namespace Tests\Feature;

use App\Filament\Pages\CollectivePaymentPage;
use App\Filament\Pages\Reports;
use App\Filament\Resources\CollectivePaymentResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DebtResource;
use App\Filament\Resources\InstallmentResource;
use App\Filament\Resources\PaymentHistoryResource;
use App\Filament\Resources\UserResource;
use App\Models\CollectivePayment;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_access_all_pages(): void
    {
        $admin = User::where('email', 'admin@hutang.test')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSuccessful();

        $pages = [
            CustomerResource::getUrl('index'),
            CustomerResource::getUrl('create'),
            DebtResource::getUrl('index'),
            InstallmentResource::getUrl('index'),
            PaymentHistoryResource::getUrl('index'),
            CollectivePaymentResource::getUrl('index'),
            UserResource::getUrl('index'),
            CollectivePaymentPage::getUrl(),
            Reports::getUrl(),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertSuccessful();
        }

        $customer = Customer::firstOrFail();
        $this->actingAs($admin)->get(CustomerResource::getUrl('view', ['record' => $customer]))->assertSuccessful();

        $debt = Debt::firstOrFail();
        $this->actingAs($admin)->get(DebtResource::getUrl('view', ['record' => $debt]))->assertSuccessful();

        $installment = Installment::firstOrFail();
        $this->actingAs($admin)->get(InstallmentResource::getUrl('view', ['record' => $installment]))->assertSuccessful();

        $collective = CollectivePayment::firstOrFail();
        $this->actingAs($admin)->get(CollectivePaymentResource::getUrl('view', ['record' => $collective]))->assertSuccessful();
    }

    public function test_kasir_cannot_access_user_management_but_can_access_customers(): void
    {
        $kasir = User::where('email', 'kasir@hutang.test')->firstOrFail();

        $this->actingAs($kasir)->get(UserResource::getUrl('index'))->assertForbidden();

        $this->actingAs($kasir)->get(CustomerResource::getUrl('index'))->assertSuccessful();
    }
}
