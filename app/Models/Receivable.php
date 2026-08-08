<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receivable extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'invoice_number',
        'receivable_party_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'receivable_date',
        'due_date',
        'status',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'receivable_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function party()
    {
        return $this->belongsTo(ReceivableParty::class);
    }

    public function installments()
    {
        return $this->hasMany(ReceivableInstallment::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(ReceivablePaymentHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_PAID ? 'Lunas' : 'Belum Lunas';
    }

    public function getProgressAttribute(): float
    {
        if ((float) $this->amount <= 0) {
            return 0;
        }

        return round(((float) $this->paid_amount / (float) $this->amount) * 100, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_UNPAID
            && $this->due_date !== null
            && $this->due_date->isBefore(today());
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('status', self::STATUS_UNPAID)
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }
}
