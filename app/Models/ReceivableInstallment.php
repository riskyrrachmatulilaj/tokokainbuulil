<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivableInstallment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receivable_id',
        'installment_date',
        'amount',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'installment_date' => 'date',
        ];
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function party()
    {
        return $this->hasOneThrough(ReceivableParty::class, Receivable::class, 'id', 'id', 'receivable_id', 'receivable_party_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentHistory()
    {
        return $this->hasOne(ReceivablePaymentHistory::class, 'installment_id');
    }
}
