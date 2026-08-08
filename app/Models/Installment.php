<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'debt_id',
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

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Debt::class, 'id', 'id', 'debt_id', 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentHistory()
    {
        return $this->hasOne(PaymentHistory::class);
    }
}
