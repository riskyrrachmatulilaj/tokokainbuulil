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
    public const PAYMENT_METHOD_SPLIT = 'split';

    protected $fillable = [
        'transaction_number',
        'sale_date',
        'payment_method',
        'receivable_party_id',
        'receivable_id',
        'total_amount',
        'cash_amount',
        'transfer_amount',
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
            'cash_amount' => 'decimal:2',
            'transfer_amount' => 'decimal:2',
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
            self::PAYMENT_METHOD_SPLIT => 'Tunai + Transfer',
            default => 'Tunai',
        };
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getWhatsappMessageTextAttribute(): string
    {
        $partyName = $this->party?->name ?? 'Pelanggan Umum';
        $dateStr = $this->sale_date ? $this->sale_date->format('d M Y') : today()->format('d M Y');

        $message = "*NOTA PEMBELIAN - TOKO KAIN BU ULIL*\n";
        $message .= "----------------------------------------\n";
        $message .= "*No. Transaksi:* " . $this->transaction_number . "\n";
        $message .= "*Tanggal:* " . $dateStr . "\n";
        $message .= "*Pelanggan:* " . $partyName . "\n";
        $message .= "*Metode Bayar:* " . $this->payment_method_label . "\n";
        $message .= "----------------------------------------\n";
        $message .= "*Rincian Barang:*\n";

        foreach ($this->items as $item) {
            $message .= "- " . $item->product_name . " (" . (int)$item->quantity . "x @ Rp " . number_format((float)$item->price, 0, ',', '.') . "): Rp " . number_format((float)$item->subtotal, 0, ',', '.') . "\n";
        }

        $message .= "----------------------------------------\n";
        $message .= "*Total Belanja:* Rp " . number_format((float)$this->total_amount, 0, ',', '.') . "\n";

        if ($this->payment_method === self::PAYMENT_METHOD_CASH) {
            $message .= "*Bayar:* Rp " . number_format((float)$this->received_amount, 0, ',', '.') . "\n";
            $message .= "*Kembalian:* Rp " . number_format((float)$this->change_amount, 0, ',', '.') . "\n";
        } elseif ($this->payment_method === self::PAYMENT_METHOD_SPLIT) {
            $message .= "*Bayar Tunai:* Rp " . number_format((float)$this->cash_amount, 0, ',', '.') . "\n";
            $message .= "*Bayar Transfer:* Rp " . number_format((float)$this->transfer_amount, 0, ',', '.') . "\n";
            $message .= "*Kembalian:* Rp " . number_format((float)$this->change_amount, 0, ',', '.') . "\n";
        } elseif ($this->payment_method === self::PAYMENT_METHOD_RECEIVABLE) {
            $message .= "*Sisa Piutang (Kredit):* Rp " . number_format((float)$this->total_amount, 0, ',', '.') . "\n";
            if ($this->receivable && $this->receivable->due_date) {
                $message .= "*Jatuh Tempo:* " . $this->receivable->due_date->format('d M Y') . "\n";
            }
        }

        $message .= "----------------------------------------\n";
        $message .= "📄 *Lihat / Unduh Nota PDF Resmi:*\n";
        $message .= route('sales.public-nota', ['sale' => $this->id]) . "\n";
        $message .= "----------------------------------------\n";
        $message .= "Terima kasih telah berbelanja di toko kami! 🙏";

        return $message;
    }

    public function getWaLinkForPhone(?string $phoneInput): ?string
    {
        if (empty($phoneInput)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $phoneInput);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (strlen($phone) < 8) {
            return null;
        }

        return "https://api.whatsapp.com/send?phone=" . $phone . "&text=" . rawurlencode($this->whatsapp_message_text);
    }

    public function getWhatsappLinkAttribute(): ?string
    {
        $party = $this->party;

        if (! $party || ! $party->phone) {
            return null;
        }

        return $this->getWaLinkForPhone($party->phone);
    }
}
