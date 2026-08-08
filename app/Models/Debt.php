<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'amount',
        'paid_amount',
        'remaining_amount',
        'debt_date',
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
            'debt_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
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
