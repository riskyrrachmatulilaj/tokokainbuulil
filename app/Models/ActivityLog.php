<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'module',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function moduleOptions(): array
    {
        return [
            'Penjualan' => '🛍️ Penjualan & Kasir',
            'Piutang' => '👥 Piutang Pelanggan',
            'Hutang' => '🏷️ Hutang Toko',
            'Produk' => '📦 Produk',
            'Pelanggan' => '🧑 Pelanggan & Supplier',
            'Pengguna' => '⚙️ Pengguna & Akses',
        ];
    }
}
