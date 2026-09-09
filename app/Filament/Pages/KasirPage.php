<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\SaleDraft;
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

    public bool $showPreviewModal = false;

    public bool $showDraftListModal = false;

    public bool $showSaveDraftModal = false;

    public bool $showCustomerDisplayModal = false;

    public string $draftReferenceName = '';

    public function openCustomerDisplayModal(): void
    {
        $this->showCustomerDisplayModal = true;
    }

    public function closeCustomerDisplayModal(): void
    {
        $this->showCustomerDisplayModal = false;
    }

    public function mount(): void
    {
        $this->saleDate = today()->format('Y-m-d');
        $this->syncCustomerDisplayState();
    }

    public function rendered(): void
    {
        $this->syncCustomerDisplayState();
    }

    public function syncCustomerDisplayState(): void
    {
        try {
            $cart = $this->cart ?: [];
            $total = $this->cartTotal();
            $itemsCount = (float) collect($cart)->sum('quantity');

            if ($this->result && ! empty($this->result['sale_id'])) {
                $status = 'success';
                $payload = [
                    'status' => 'success',
                    'cart' => [],
                    'total_amount' => (float) ($this->result['total'] ?? 0),
                    'items_count' => (float) ($this->result['items_count'] ?? 0),
                    'payment_method' => $this->result['payment_method'] ?? null,
                    'received_amount' => $this->result['received'] ?? null,
                    'change_amount' => $this->result['change'] ?? null,
                    'down_payment' => $this->result['down_payment'] ?? null,
                    'remaining_credit' => $this->result['remaining_credit'] ?? null,
                    'transaction_number' => $this->result['transaction_number'] ?? null,
                    'party_name' => $this->result['party_name'] ?? null,
                ];
            } else {
                $hasReceived = $this->receivedAmount !== null && $this->receivedAmount !== '';
                $hasDp = ($this->cashAmount > 0 || $this->transferAmount > 0);
                $status = ! empty($cart) ? (($hasReceived || $hasDp) ? 'payment' : 'active') : 'idle';

                $recAmount = static::parseNumericAmount($this->receivedAmount);
                $change = ($recAmount !== null && $recAmount >= $total) ? round($recAmount - $total, 2) : null;
                $cashDp = (float) static::parseNumericAmount($this->cashAmount) ?: 0;
                $trfDp = (float) static::parseNumericAmount($this->transferAmount) ?: 0;
                $dp = $cashDp + $trfDp;
                $party = $this->getSelectedParty();

                $payload = [
                    'status' => $status,
                    'cart' => $cart,
                    'total_amount' => $total,
                    'items_count' => $itemsCount,
                    'last_item' => ! empty($cart) ? end($cart) : null,
                    'payment_method' => $this->paymentMethod,
                    'received_amount' => $recAmount,
                    'change_amount' => $change,
                    'cash_amount' => $cashDp > 0 ? $cashDp : null,
                    'transfer_amount' => $trfDp > 0 ? $trfDp : null,
                    'down_payment' => $dp > 0 ? $dp : null,
                    'remaining_credit' => $dp > 0 ? max(0, $total - $dp) : null,
                    'transaction_number' => null,
                    'party_name' => $party?->name ?? ($this->partySearch ?: null),
                ];
            }

            $timestamp = microtime(true);
            $payload['updated_at'] = now()->toIso8601String();
            $payload['timestamp'] = $timestamp;

            \Illuminate\Support\Facades\Cache::put('pos_customer_display_state', $payload, now()->addHours(6));
            \Illuminate\Support\Facades\Cache::put('pos_customer_display_timestamp', $timestamp, now()->addHours(6));

            $this->dispatch('customer-display-synced', $payload);
        } catch (\Throwable $e) {
            // Safe fail
        }
    }

    public function openPreviewModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->warning()
                ->title('Keranjang Kosong')
                ->body('Tambahkan produk ke keranjang terlebih dahulu sebelum melihat pratinjau nota.')
                ->send();

            return;
        }

        $this->showPreviewModal = true;
    }

    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
    }

    public function printDraftNota(): void
    {
        $this->dispatch('do-print-draft-nota');
    }

    public function getActiveDraftsProperty(): Collection
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('sale_drafts')) {
                return collect();
            }

            return SaleDraft::with('party')
                ->orderBy('updated_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function getActiveDraftsCountProperty(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('sale_drafts')) {
                return 0;
            }

            return SaleDraft::count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function openSaveDraftModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->warning()
                ->title('Keranjang Kosong')
                ->body('Tambahkan produk ke keranjang terlebih dahulu sebelum menyimpan draft.')
                ->send();

            return;
        }

        $party = $this->getSelectedParty();
        $this->draftReferenceName = $party ? $party->name : ('Draft ' . now()->format('H:i'));
        $this->showSaveDraftModal = true;
    }

    public function closeSaveDraftModal(): void
    {
        $this->showSaveDraftModal = false;
    }

    public function saveDraft(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->warning()
                ->title('Keranjang Kosong')
                ->body('Tambahkan produk ke keranjang terlebih dahulu sebelum menyimpan draft.')
                ->send();

            return;
        }

        $refName = trim($this->draftReferenceName) !== '' ? trim($this->draftReferenceName) : ('Draft ' . now()->format('H:i'));

        SaleDraft::create([
            'user_id' => auth()->id(),
            'reference_name' => $refName,
            'cart_data' => $this->cart,
            'receivable_party_id' => $this->receivablePartyId,
            'payment_method' => $this->paymentMethod,
            'sale_date' => $this->saleDate,
            'total_amount' => $this->cartTotal(),
        ]);

        $this->clearCart();
        $this->showSaveDraftModal = false;

        Notification::make()
            ->success()
            ->title('Draft Berhasil Disimpan')
            ->body("Transaksi \"{$refName}\" disimpan sebagai draft.")
            ->send();
    }

    public function openDraftListModal(): void
    {
        $this->showDraftListModal = true;
    }

    public function closeDraftListModal(): void
    {
        $this->showDraftListModal = false;
    }

    public function loadDraft(int $draftId): void
    {
        $draft = SaleDraft::find($draftId);

        if (! $draft) {
            Notification::make()
                ->danger()
                ->title('Draft Tidak Ditemukan')
                ->body('Draft tersebut mungkin sudah dihapus atau dimuat sebelumnya.')
                ->send();

            return;
        }

        // If current cart is not empty, auto-save it as draft first
        if (! empty($this->cart)) {
            $currentParty = $this->getSelectedParty();
            $autoRef = $currentParty ? ($currentParty->name . ' (Auto)') : ('Draft ' . now()->format('H:i'));
            SaleDraft::create([
                'user_id' => auth()->id(),
                'reference_name' => $autoRef,
                'cart_data' => $this->cart,
                'receivable_party_id' => $this->receivablePartyId,
                'payment_method' => $this->paymentMethod,
                'sale_date' => $this->saleDate,
                'total_amount' => $this->cartTotal(),
            ]);
        }

        $this->cart = $draft->cart_data ?: [];
        $this->receivablePartyId = $draft->receivable_party_id;
        $this->partySearch = $draft->party?->name ?? '';
        $this->paymentMethod = $draft->payment_method ?: Sale::PAYMENT_METHOD_CASH;
        $this->saleDate = $draft->sale_date ? $draft->sale_date->format('Y-m-d') : today()->format('Y-m-d');
        $this->receivedAmount = null;
        $this->cashAmount = null;
        $this->transferAmount = null;

        $refName = $draft->reference_name;
        $draft->delete();

        $this->showDraftListModal = false;

        Notification::make()
            ->success()
            ->title('Draft Berhasil Dimuat')
            ->body("Draft \"{$refName}\" dimuat ke keranjang.")
            ->send();
    }

    public function deleteDraft(int $draftId): void
    {
        $draft = SaleDraft::find($draftId);
        if ($draft) {
            $refName = $draft->reference_name;
            $draft->delete();

            Notification::make()
                ->success()
                ->title('Draft Dihapus')
                ->body("Draft \"{$refName}\" telah dihapus.")
                ->send();
        }
    }

    public function getSelectedParty(): ?ReceivableParty
    {
        return $this->receivablePartyId ? ReceivableParty::find($this->receivablePartyId) : null;
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->paymentMethod) {
            Sale::PAYMENT_METHOD_CASH => 'Tunai',
            Sale::PAYMENT_METHOD_TRANSFER => 'Transfer',
            Sale::PAYMENT_METHOD_SPLIT => 'Tunai + Transfer',
            Sale::PAYMENT_METHOD_RECEIVABLE => 'Kredit (Piutang)',
            Sale::PAYMENT_METHOD_CREDIT_CASH => 'Kredit + Tunai',
            Sale::PAYMENT_METHOD_CREDIT_TRANSFER => 'Kredit + Transfer',
            Sale::PAYMENT_METHOD_CREDIT_SPLIT => 'Kredit + Tunai + Transfer',
            default => ucfirst($this->paymentMethod),
        };
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
            ->limit(25)
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
            if ($party && strtolower(trim($party->name)) !== strtolower(trim($value))) {
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
        $this->showPreviewModal = false;
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

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_CREDIT_CASH) {
                $data['cash_amount'] = static::parseNumericAmount($this->cashAmount);
            }

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_CREDIT_TRANSFER) {
                $data['transfer_amount'] = static::parseNumericAmount($this->transferAmount);
            }

            if ($this->paymentMethod === Sale::PAYMENT_METHOD_CREDIT_SPLIT) {
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
                'down_payment' => (float) $sale->down_payment,
                'remaining_credit' => (float) $sale->remaining_credit,
                'items_count' => $sale->items->sum('quantity'),
                'wa_link' => $sale->whatsapp_link,
            ];

            $this->cart = [];
            $this->receivedAmount = null;
            $this->cashAmount = null;
            $this->transferAmount = null;
            $this->receivablePartyId = null;
            $this->partySearch = '';
            $this->showPreviewModal = false;

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
        } catch (\Throwable $e) {
            logger()->error('Kasir processSale error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Notification::make()
                ->danger()
                ->title('Terjadi Kendala')
                ->body($e->getMessage() ?: 'Gagal memproses transaksi penjualan.')
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
