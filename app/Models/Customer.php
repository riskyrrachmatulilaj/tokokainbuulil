<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function collectivePayments()
    {
        return $this->hasMany(CollectivePayment::class);
    }

    public function unpaidDebts()
    {
        return $this->debts()
            ->where('status', Debt::STATUS_UNPAID)
            ->orderBy('debt_date');
    }

    public function scopeSearch($query, string $term): mixed
    {
        $words = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return $query;
        }

        return $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->where(function ($sub) use ($word) {
                    $sub->where('name', 'like', "%{$word}%")
                        ->orWhere('phone', 'like', "%{$word}%")
                        ->orWhere('address', 'like', "%{$word}%");
                });
            }
        });
    }

    public function getHasUnpaidDebtsAttribute(): bool
    {
        return $this->debts()
            ->where('status', Debt::STATUS_UNPAID)
            ->exists();
    }

    protected static function booted(): void
    {
        static::created(function (Customer $customer) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pelanggan',
                'create',
                "Menambah data supplier baru '{$customer->name}'",
                $customer,
                ['name' => $customer->name, 'phone' => $customer->phone]
            );
        });

        static::updated(function (Customer $customer) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pelanggan',
                'update',
                "Mengubah data supplier '{$customer->name}'",
                $customer
            );
        });

        static::deleted(function (Customer $customer) {
            app(\App\Services\ActivityLogService::class)->log(
                'Pelanggan',
                'delete',
                "Menghapus data supplier '{$customer->name}'",
                $customer,
                ['name' => $customer->name]
            );
        });
    }
}
