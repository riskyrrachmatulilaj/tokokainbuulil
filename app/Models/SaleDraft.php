<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference_name',
        'cart_data',
        'receivable_party_id',
        'payment_method',
        'sale_date',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'cart_data' => 'array',
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(ReceivableParty::class, 'receivable_party_id');
    }
}
