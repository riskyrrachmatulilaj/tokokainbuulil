<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Menghapus pelanggan. Dilarang apabila masih memiliki nota belum lunas.
     */
    public function deleteCustomer(Customer $customer): void
    {
        if ($customer->debts()->where('status', Debt::STATUS_UNPAID)->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'Pelanggan masih memiliki nota yang belum lunas dan tidak dapat dihapus.',
            ]);
        }

        $customer->delete();
    }
}
