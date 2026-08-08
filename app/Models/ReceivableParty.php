<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivableParty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(ReceivablePaymentHistory::class);
    }

    public function collectivePayments()
    {
        return $this->hasMany(ReceivableCollectivePayment::class);
    }

    public function unpaidReceivables()
    {
        return $this->receivables()
            ->where('status', Receivable::STATUS_UNPAID)
            ->orderBy('receivable_date');
    }

    public function scopeSearch($query, string $term): mixed
    {
        return $query->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%"));
    }

    public function getHasUnpaidReceivablesAttribute(): bool
    {
        return $this->receivables()
            ->where('status', Receivable::STATUS_UNPAID)
            ->exists();
    }
}
