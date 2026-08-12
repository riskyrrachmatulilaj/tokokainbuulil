<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'track_stock',
        'stock',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'track_stock' => 'bool',
            'stock' => 'decimal:2',
            'is_active' => 'bool',
        ];
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getPriceLabelAttribute(): string
    {
        return rupiah($this->price);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getStockLabelAttribute(): string
    {
        if (! $this->track_stock) {
            return 'Tanpa Batas';
        }

        $formattedStock = (float) $this->stock == (int) $this->stock 
            ? (string) (int) $this->stock 
            : number_format((float) $this->stock, 2, ',', '.');

        return 'Stok: ' . $formattedStock;
    }

    public function hasEnoughStock(float $requestedQty): bool
    {
        if (! $this->track_stock) {
            return true;
        }

        return (float) ($this->stock ?? 0) >= $requestedQty;
    }

    public function deductStock(float $qty): void
    {
        if (! $this->track_stock) {
            return;
        }

        $newStock = max(0, (float) ($this->stock ?? 0) - $qty);
        $this->update(['stock' => $newStock]);
    }

    public function restoreStock(float $qty): void
    {
        if (! $this->track_stock) {
            return;
        }

        $newStock = (float) ($this->stock ?? 0) + $qty;
        $this->update(['stock' => $newStock]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query, float $threshold = 5.0)
    {
        return $query->where('track_stock', true)->where('stock', '<=', $threshold);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }
}
