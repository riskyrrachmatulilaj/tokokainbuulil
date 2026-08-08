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

    public ?float $receivedAmount = null;

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
                    $sub->where('name', 'like', '%' . $this->partySearch . '%')
                        ->orWhere('phone', 'like', '%' . $this->partySearch . '%');
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

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['quantity'] += 1;
            $this->cart[$existingIndex]['subtotal'] = round((float) $this->cart[$existingIndex]['price'] * $this->cart[$existingIndex]['quantity'], 2);
        } else {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
                'subtotal' => (float) $product->price,
            ];
        }
    }

    public function incrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity'] += 1;
            $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $this->cart[$index]['quantity'], 2);
        }
    }

    public function decrementQty(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($this->cart[$index]['quantity'] <= 1) {
            $this->removeFromCart($index);

            return;
        }

        $this->cart[$index]['quantity'] -= 1;
        $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $this->cart[$index]['quantity'], 2);
    }

    /**
     * Set kuantitas item keranjang langsung (ketik angka).
     * Nilai di bawah 1 akan menghapus item dari keranjang.
     */
    public function setQty(int $index, mixed $quantity): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $qty = (int) $quantity;

        if ($qty < 1) {
            $this->removeFromCart($index);

            return;
        }

        $this->cart[$index]['quantity'] = $qty;
        $this->cart[$index]['subtotal'] = round((float) $this->cart[$index]['price'] * $qty, 2);
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->receivedAmount = null;
        $this->receivablePartyId = null;
        $this->partySearch = '';
    }

    public function cartTotal(): float
    {
        return round(array_sum(array_column($this->cart, 'subtotal')), 2);
    }

    public function changeAmount(): float
    {
        return max(0, round((float) ($this->receivedAmount ?? 0) - $this->cartTotal(), 2));
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
                    'quantity' => $row['quantity'],
                ])->all(),
                'payment_method' => $this->paymentMethod,
                'receivable_party_id' => $this->receivablePartyId,
                'sale_date' => $this->saleDate,
            ];

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_CASH) {
                $data['received_amount'] = $this->receivedAmount;
            }

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_TRANSFER) {
                $data['received_amount'] = null;
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
                'received' => $sale->received_amount !== null ? (float) $sale->received_amount : null,
                'change' => $sale->change_amount !== null ? (float) $sale->change_amount : null,
                'items_count' => $sale->items->sum('quantity'),
            ];

            $this->cart = [];
            $this->receivedAmount = null;
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
