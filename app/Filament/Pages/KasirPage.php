<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class KasirPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string | \UnitEnum | null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Layar Kasir';

    protected static ?string $title = 'Layar Kasir';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kasir';

    public string $search = '';

    public array $cart = [];

    public string $paymentMethod = Sale::PAYMENT_METHOD_CASH;

    public mixed $receivedAmount = null;

    public mixed $cashAmount = null;

    public mixed $transferAmount = null;

    public ?int $receivablePartyId = null;

    public ?string $saleDate = null;

    public ?array $result = null;

    public string $partySearch = '';

    public function mount(): void
    {
        $this->saleDate = today()->format('Y-m-d');
    }

    public function products(): Collection
    {
        return Product::active()
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->orderBy('name')
            ->get();
    }

    public function parties(): Collection
    {
        return ReceivableParty::query()
            ->when($this->partySearch !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->search($this->partySearch);
                });
                if ($this->receivablePartyId) {
                    $q->orWhere('id', $this->receivablePartyId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function selectParty(int $partyId): void
    {
        $party = ReceivableParty::find($partyId);
        if ($party) {
            $this->receivablePartyId = $party->id;
            $this->partySearch = $party->name;
        }
    }

    public function clearSelectedParty(): void
    {
        $this->receivablePartyId = null;
        $this->partySearch = '';
    }

    public function updatedPartySearch(string $value): void
    {
        if ($this->receivablePartyId) {
            $party = ReceivableParty::find($this->receivablePartyId);
            if ($party && $party->name !== $value) {
                $this->receivablePartyId = null;
            }
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::active()->whereKey($productId)->first();

        if (! $product) {
            Notification::make()
                ->danger()
                ->title('Produk tidak ditemukan')
                ->send();

            return;
        }

        $existingIndex = collect($this->cart)->search(fn (array $row) => $row['product_id'] === $product->id);
        $newQty = ($existingIndex !== false) ? round((float) $this->cart[$existingIndex]['quantity'] + 1, 3) : 1.0;

        if (! $product->hasEnoughStock($newQty)) {
            $stockText = (float) $product->stock == (int) $product->stock ? (int) $product->stock : number_format((float) $product->stock, 2, ',', '.');
            Notification::make()
                ->warning()
                ->title('Stok Tidak Mencukupi')
                ->body("Stok produk \"{$product->name}\" tersisa {$stockText}.")
                ->send();

            return;
        }

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['quantity'] = $newQty;
            $this->cart[$existingIndex]['subtotal'] = round((float) $this->cart[$existingIndex]['price'] * $newQty, 2);
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'notes' => '',
                'original_price' => (float) $product->price,
                'price' => (float) $product->price,
                'quantity' => 1,
                'subtotal' => (float) $product->price,
            ];
        }
    }

    public function incrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $newQty = round((float) $this->cart[$index]['quantity'] + 1, 3);
            $product = Product::find($this->cart[$index]['product_id'] ?? null);

            if ($product && ! $product->hasEnoughStock($newQty)) {
                $stockText = (float) $product->stock == (int) $product->stock ? (int) $product->stock : number_format((float) $product->stock, 2, ',', '.');
                Notification::make()
                    ->warning()
                    ->title('Stok Tidak Mencukupi')
                    ->body("Stok produk \"{$product->name}\" tersisa {$stockText}.")
                    ->send();

                return;
            }

            $this->cart[$index]['quantity'] = $newQty;
            $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $newQty, 2);
        }
    }

    public function decrementQty(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $newQty = round((float) $this->cart[$index]['quantity'] - 1, 3);

        if ($newQty <= 0) {
            $this->removeFromCart($index);

            return;
        }

        $this->cart[$index]['quantity'] = $newQty;
        $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $newQty, 2);
    }

    /**
     * Set kuantitas item keranjang langsung (ketik angka pecahan/desimal seperti 1.5, 0.5, 4.3).
     * Nilai di bawah atau sama dengan 0 akan menghapus item dari keranjang.
     */
    public function setQty(int $index, mixed $quantity): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $qty = static::parseNumericAmount($quantity);

        if ($qty === null || $qty <= 0) {
            $this->removeFromCart($index);

            return;
        }

        $product = Product::find($this->cart[$index]['product_id'] ?? null);

        if ($product && ! $product->hasEnoughStock($qty)) {
            $stockText = (float) $product->stock == (int) $product->stock ? (int) $product->stock : number_format((float) $product->stock, 2, ',', '.');
            Notification::make()
                ->warning()
                ->title('Stok Tidak Mencukupi')
                ->body("Stok produk \"{$product->name}\" tersisa {$stockText}.")
                ->send();

            return;
        }

        $this->cart[$index]['quantity'] = $qty;
        $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $qty, 2);
    }

    /**
     * Set harga satuan item keranjang secara manual.
     */
    public function setPrice(int $index, mixed $price): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $newPrice = max(0, (float) (static::parseNumericAmount($price) ?? 0));
        $this->cart[$index]['price'] = $newPrice;
        $this->cart[$index]['subtotal'] = round($newPrice * (float) $this->cart[$index]['quantity'], 2);
    }

    public function setNotes(int $index, mixed $value): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['notes'] = is_string($value) ? trim($value) : (string) $value;
        }
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updatedReceivedAmount(mixed $value): void
    {
        $this->receivedAmount = static::parseNumericAmount($value);
    }

    public function updatedCashAmount(mixed $value): void
    {
        $this->cashAmount = static::parseNumericAmount($value);
    }

    public function updatedTransferAmount(mixed $value): void
    {
        $this->transferAmount = static::parseNumericAmount($value);
    }

    public static function parseNumericAmount(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_numeric($amount) && ! is_string($amount)) {
            return (float) $amount;
        }

        $str = trim((string) $amount);
        if ($str === '') {
            return null;
        }

        if (str_contains($str, '.') && ! str_contains($str, ',')) {
            $parts = explode('.', $str);
            if (count($parts) > 1) {
                $isThousand = true;
                for ($i = 1; $i < count($parts); $i++) {
                    if (strlen($parts[$i]) !== 3) {
                        $isThousand = false;
                        break;
                    }
                }
                if ($isThousand) {
                    $str = implode('', $parts);
                }
            }
        } elseif (str_contains($str, ',') && ! str_contains($str, '.')) {
            $parts = explode(',', $str);
            if (count($parts) > 1) {
                $isThousand = true;
                for ($i = 1; $i < count($parts); $i++) {
                    if (strlen($parts[$i]) !== 3) {
                        $isThousand = false;
                        break;
                    }
                }
                if ($isThousand) {
                    $str = implode('', $parts);
                } else {
                    $str = str_replace(',', '.', $str);
                }
            }
        } elseif (str_contains($str, '.') && str_contains($str, ',')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }

        $val = (float) $str;

        return is_nan($val) ? null : round($val, 2);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->receivedAmount = null;
        $this->cashAmount = null;
        $this->transferAmount = null;
        $this->receivablePartyId = null;
        $this->partySearch = '';
    }

    public function cartTotal(): float
    {
        return round(array_sum(array_column($this->cart, 'subtotal')), 2);
    }

    public function changeAmount(): float
    {
        $total = $this->cartTotal();

        if ($this->paymentMethod === Sale::PAYMENT_METHOD_CASH) {
            $received = static::parseNumericAmount($this->receivedAmount) ?? 0.0;
            return max(0.0, round($received - $total, 2));
        }

        if ($this->paymentMethod === Sale::PAYMENT_METHOD_SPLIT) {
            $cash = static::parseNumericAmount($this->cashAmount) ?? 0.0;
            $transfer = static::parseNumericAmount($this->transferAmount) ?? 0.0;
            return max(0.0, round(($cash + $transfer) - $total, 2));
        }

        return 0.0;
    }

    public function processSale(): void
    {
        try {
            if (empty($this->cart)) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang masih kosong.',
                ]);
            }

            $data = [
                'items' => collect($this->cart)->map(fn (array $row) => [
                    'product_id' => $row['product_id'],
                    'price' => (float) $row['price'],
                    'quantity' => $row['quantity'],
                    'notes' => isset($row['notes']) && trim((string)$row['notes']) !== '' ? trim((string)$row['notes']) : null,
                ])->all(),
                'payment_method' => $this->paymentMethod,
                'receivable_party_id' => $this->receivablePartyId,
                'sale_date' => $this->saleDate,
            ];

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_CASH) {
                $data['received_amount'] = static::parseNumericAmount($this->receivedAmount);
            }

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_TRANSFER) {
                $data['received_amount'] = null;
            }

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_SPLIT) {
                $data['cash_amount'] = static::parseNumericAmount($this->cashAmount);
                $data['transfer_amount'] = static::parseNumericAmount($this->transferAmount);
            }

            $sale = app(SaleService::class)->createSale($data, auth()->user());

            $this->result = [
                'sale_id' => $sale->id,
                'transaction_number' => $sale->transaction_number,
                'payment_method' => $sale->payment_method,
                'payment_method_label' => $sale->payment_method_label,
                'party_name' => $sale->party?->name,
                'receivable_invoice' => $sale->receivable?->invoice_number,
                'total' => (float) $sale->total_amount,
                'cash_amount' => $sale->cash_amount !== null ? (float) $sale->cash_amount : null,
                'transfer_amount' => $sale->transfer_amount !== null ? (float) $sale->transfer_amount : null,
                'received' => $sale->received_amount !== null ? (float) $sale->received_amount : null,
                'change' => $sale->change_amount !== null ? (float) $sale->change_amount : null,
                'items_count' => $sale->items->sum('quantity'),
                'wa_link' => $sale->whatsapp_link,
            ];

            $this->cart = [];
            $this->receivedAmount = null;
            $this->cashAmount = null;
            $this->transferAmount = null;
            $this->receivablePartyId = null;
            $this->partySearch = '';

            Notification::make()
                ->success()
                ->title('Penjualan berhasil')
                ->body('Transaksi '.$sale->transaction_number.' selesai.')
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Penjualan gagal')
                ->body(collect($e->errors())->flatten()->first())
                ->send();
        }
    }

    public function printNota()
    {
        $sale = Sale::findOrFail($this->result['sale_id'] ?? 0);

        return SalePdfService::nota($sale);
    }

    public function printThermal()
    {
        $sale = Sale::findOrFail($this->result['sale_id'] ?? 0);

        return SaleThermalService::nota($sale);
    }
}
