<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivableCollectivePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'receivable_party_id',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function history()
    {
        return $this->hasMany(ReceivablePaymentHistory::class, 'collective_payment_id');
    }
}
