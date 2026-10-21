<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DataChangeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_EDIT = 'edit';
    public const TYPE_VOID = 'void';

    protected $fillable = [
        'user_id',
        'requestable_type',
        'requestable_id',
        'request_type',
        'reason',
        'original_data',
        'proposed_data',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'original_data' => 'array',
            'proposed_data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_EDIT => 'Koreksi Data',
            self::TYPE_VOID => 'Pembatalan Transaksi',
            default => ucfirst($this->request_type),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }

    public function getModuleNameAttribute(): string
    {
        return match ($this->requestable_type) {
            \App\Models\Sale::class => 'Penjualan',
            \App\Models\Debt::class => 'Hutang Toko',
            \App\Models\Receivable::class => 'Piutang Pelanggan',
            default => class_basename($this->requestable_type),
        };
    }

    public function getModuleBadgeColorAttribute(): string
    {
        return match ($this->requestable_type) {
            \App\Models\Sale::class => 'primary',
            \App\Models\Debt::class => 'warning',
            \App\Models\Receivable::class => 'info',
            default => 'gray',
        };
    }

    public function getDocumentNumberAttribute(): string
    {
        return $this->original_data['invoice_number']
            ?? $this->original_data['transaction_number']
            ?? "#{$this->requestable_id}";
    }
}
