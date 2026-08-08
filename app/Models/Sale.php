<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_RECEIVABLE = 'receivable';
    public const PAYMENT_METHOD_TRANSFER = 'transfer';

    protected $fillable = [
        'transaction_number',
        'sale_date',
        'payment_method',
        'receivable_party_id',
        'receivable_id',
        'total_amount',
        'received_amount',
        'change_amount',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function party()
    {
        return $this->belongsTo(ReceivableParty::class, 'receivable_party_id');
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class, 'receivable_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_METHOD_RECEIVABLE => 'Kredit (Piutang)',
            self::PAYMENT_METHOD_TRANSFER => 'Transfer',
            default => 'Tunai',
        };
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
