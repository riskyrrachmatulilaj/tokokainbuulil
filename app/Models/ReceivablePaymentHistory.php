<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivablePaymentHistory extends Model
{
    use HasFactory;

    public const TYPE_INSTALLMENT = 'installment';
    public const TYPE_COLLECTIVE = 'collective';

    protected $fillable = [
        'transaction_number',
        'receivable_party_id',
        'receivable_id',
        'installment_id',
        'collective_payment_id',
        'payment_type',
        'amount',
        'payment_date',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function party()
    {
        return $this->belongsTo(ReceivableParty::class, 'receivable_party_id');
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function installment()
    {
        return $this->belongsTo(ReceivableInstallment::class);
    }

    public function collectivePayment()
    {
        return $this->belongsTo(ReceivableCollectivePayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->payment_type === self::TYPE_COLLECTIVE ? 'Pembayaran Kolektif' : 'Cicilan Nota';
    }

    public function scopeTypeInstallment($query)
    {
        return $query->where('payment_type', self::TYPE_INSTALLMENT);
    }

    public function scopeTypeCollective($query)
    {
        return $query->where('payment_type', self::TYPE_COLLECTIVE);
    }
}
