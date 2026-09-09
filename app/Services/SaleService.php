<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Membuat transaksi penjualan beserta rincian item.
     *
     * - Pembayaran tunai / kredit / transfer: wajib memilih pelanggan (ReceivableParty).
     * - Pembayaran tunai: memvalidasi uang diterima dan menghitung kembalian.
     * - Pembayaran transfer: uang sudah diterima, tanpa input manual.
     * - Pembayaran kredit: otomatis membuat nota piutang (modul piutang).
     *
     * Seluruh proses berjalan dalam satu database transaction.
     *
     * @param  array{items: array<int, array{product_id: int, quantity: int}>, payment_method?: string, receivable_party_id?: int|null, received_amount?: float|int|null, sale_date?: string, description?: string|null}  $data
     */
    public function createSale(array $data, ?User $user = null): Sale
    {
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Keranjang penjualan masih kosong.',
            ]);
        }

        $method = $data['payment_method'] ?? Sale::PAYMENT_METHOD_CASH;
        $saleDate = $data['sale_date'] ?? today();

        return DB::transaction(function () use ($data, $items, $method, $saleDate, $user) {
            $lines = [];
            $total = 0.0;

            foreach ($items as $item) {
                $product = Product::active()->whereKey($item['product_id'] ?? null)->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk tidak ditemukan atau sudah nonaktif.',
                    ]);
                }

                $quantity = max(0.001, round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($item['quantity'] ?? 1) ?? 1), 3));

                if (! $product->hasEnoughStock($quantity)) {
                    $stockText = (float) $product->stock == (int) $product->stock ? (int) $product->stock : number_format((float) $product->stock, 2, ',', '.');
                    throw ValidationException::withMessages([
                        'items' => "Stok produk \"{$product->name}\" tidak mencukupi (Tersedia: {$stockText}).",
                    ]);
                }

                $price = isset($item['price']) && is_numeric($item['price']) && (float) $item['price'] >= 0
                    ? (float) $item['price']
                    : (float) $product->price;

                $lineTotal = round($price * $quantity, 2);

                $total = round($total + $lineTotal, 2);

                $lines[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'notes' => isset($item['notes']) && trim((string) $item['notes']) !== '' ? trim((string) $item['notes']) : null,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $lineTotal,
                ];
            }

            $party = ReceivableParty::find($data['receivable_party_id'] ?? null);

            if (! $party) {
                throw ValidationException::withMessages([
                    'receivable_party_id' => 'Pilih pelanggan terlebih dahulu.',
                ]);
            }

            $received = null;
            $change = null;
            $cashAmount = null;
            $transferAmount = null;

            if ($method === Sale::PAYMENT_METHOD_CASH) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['received_amount'] ?? null) ?? 0), 2);
                $received = $cashAmount;

                if ($received < $total) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Uang yang diterima kurang dari total belanja ('.number_format($total, 2).').',
                    ]);
                }

                $change = round($received - $total, 2);
            } elseif ($method === Sale::PAYMENT_METHOD_TRANSFER) {
                $cashAmount = 0;
                $transferAmount = $total;
                $received = $total;
                $change = 0;
            } elseif ($method === Sale::PAYMENT_METHOD_SPLIT) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = round($cashAmount + $transferAmount, 2);

                if ($received < $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Jumlah pembayaran (Tunai + Transfer) kurang dari total belanja ('.number_format($total, 2).').',
                    ]);
                }

                $change = round($received - $total, 2);
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_CASH) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = null;
                $received = $cashAmount;
                $change = 0;

                if ($cashAmount < 0) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Nominal uang muka tunai tidak boleh negatif.',
                    ]);
                }

                if ($cashAmount >= $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Uang muka tunai (Rp ' . number_format($cashAmount, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Tunai.',
                    ]);
                }
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_TRANSFER) {
                $cashAmount = null;
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = $transferAmount;
                $change = 0;

                if ($transferAmount < 0) {
                    throw ValidationException::withMessages([
                        'transfer_amount' => 'Nominal uang muka transfer tidak boleh negatif.',
                    ]);
                }

                if ($transferAmount >= $total) {
                    throw ValidationException::withMessages([
                        'transfer_amount' => 'Uang muka transfer (Rp ' . number_format($transferAmount, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Transfer.',
                    ]);
                }
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_SPLIT) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = round($cashAmount + $transferAmount, 2);
                $change = 0;

                if ($received < 0) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Nominal uang muka tidak boleh negatif.',
                    ]);
                }

                if ($received >= $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Total uang muka (Rp ' . number_format($received, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Tunai + Transfer.',
                    ]);
                }
            } else {
                // Sale::PAYMENT_METHOD_RECEIVABLE
                $cashAmount = null;
                $transferAmount = null;
                $received = null;
                $change = null;
            }

            $sale = Sale::create([
                'transaction_number' => $this->numberService->nextSaleNumber(),
                'sale_date' => $saleDate,
                'payment_method' => $method,
                'receivable_party_id' => $party->id,
                'receivable_id' => null,
                'total_amount' => $total,
                'cash_amount' => $cashAmount,
                'transfer_amount' => $transferAmount,
                'received_amount' => $received,
                'change_amount' => $change,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            $hasNotesCol = \Illuminate\Support\Facades\Schema::hasColumn('sale_items', 'notes');

            foreach ($lines as $line) {
                /** @var Product $prod */
                $prod = $line['product'];
                unset($line['product']);

                if (! $hasNotesCol) {
                    unset($line['notes']);
                }

                SaleItem::create(array_merge($line, ['sale_id' => $sale->id]));
                $prod->deductStock($line['quantity']);
            }

            $isCredit = in_array($method, [
                Sale::PAYMENT_METHOD_RECEIVABLE,
                Sale::PAYMENT_METHOD_CREDIT_CASH,
                Sale::PAYMENT_METHOD_CREDIT_TRANSFER,
                Sale::PAYMENT_METHOD_CREDIT_SPLIT,
            ]);

            if ($isCredit) {
                $downPayment = round((float) ($cashAmount ?? 0) + (float) ($transferAmount ?? 0), 2);

                $receivable = app(ReceivableService::class)->createReceivable([
                    'receivable_party_id' => $party->id,
                    'amount' => $total,
                    'receivable_date' => $saleDate,
                    'due_date' => $data['due_date'] ?? null,
                    'description' => 'Penjualan kredit '.$sale->transaction_number,
                ], $user);

                if ($downPayment > 0) {
                    $dpType = $method === Sale::PAYMENT_METHOD_CREDIT_TRANSFER ? 'Transfer' : ($method === Sale::PAYMENT_METHOD_CREDIT_SPLIT ? 'Tunai & Transfer' : 'Tunai');
                    app(ReceivablePaymentService::class)->recordInstallment([
                        'receivable_id' => $receivable->id,
                        'amount' => $downPayment,
                        'installment_date' => $saleDate,
                        'description' => "Uang Muka Transaksi {$sale->transaction_number} ({$dpType})",
                    ], $user);
                }

                $sale->update(['receivable_id' => $receivable->id]);
            }

            app(\App\Services\ActivityLogService::class)->log(
                'Penjualan',
                'create',
                "Memproses transaksi penjualan {$sale->transaction_number} ({$sale->payment_method_label}) senilai Rp " . number_format($total, 0, ',', '.') . " untuk pelanggan {$party->name}",
                $sale,
                ['total' => $total, 'payment_method' => $method, 'party' => $party->name],
                $user
            );

            return $sale->fresh()->load(['items', 'party', 'creator', 'receivable']);
        });
    }

    /**
     * Memperbarui transaksi penjualan beserta rincian item, stok, dan piutang.
     */
    public function updateSale(Sale $sale, array $data, ?User $user = null): Sale
    {
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Rincian barang penjualan tidak boleh kosong.',
            ]);
        }

        $method = $data['payment_method'] ?? $sale->payment_method;
        $saleDate = $data['sale_date'] ?? $sale->sale_date;

        return DB::transaction(function () use ($sale, $data, $items, $method, $saleDate, $user) {
            $sale->load(['items.product', 'receivable']);

            // 1. Restore old items stock
            foreach ($sale->items as $oldItem) {
                if ($oldItem->product) {
                    $oldItem->product->restoreStock($oldItem->quantity);
                }
            }

            // 2. Validate and calculate new items
            $lines = [];
            $total = 0.0;

            foreach ($items as $item) {
                $product = Product::active()->whereKey($item['product_id'] ?? null)->first()
                    ?? Product::whereKey($item['product_id'] ?? null)->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Produk tidak ditemukan.',
                    ]);
                }

                $quantity = max(0.001, round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($item['quantity'] ?? 1) ?? 1), 3));

                if (! $product->fresh()->hasEnoughStock($quantity)) {
                    $stockText = (float) $product->stock == (int) $product->stock ? (int) $product->stock : number_format((float) $product->stock, 2, ',', '.');
                    throw ValidationException::withMessages([
                        'items' => "Stok produk \"{$product->name}\" tidak mencukupi (Tersedia: {$stockText}).",
                    ]);
                }

                $price = isset($item['price']) && is_numeric($item['price']) && (float) $item['price'] >= 0
                    ? (float) $item['price']
                    : (float) $product->price;

                $lineTotal = round($price * $quantity, 2);
                $total = round($total + $lineTotal, 2);

                $lines[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'notes' => isset($item['notes']) && trim((string) $item['notes']) !== '' ? trim((string) $item['notes']) : null,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $lineTotal,
                ];
            }

            $party = ReceivableParty::find($data['receivable_party_id'] ?? $sale->receivable_party_id);

            if (! $party) {
                throw ValidationException::withMessages([
                    'receivable_party_id' => 'Pilih pelanggan terlebih dahulu.',
                ]);
            }

            $received = null;
            $change = null;
            $cashAmount = null;
            $transferAmount = null;

            if ($method === Sale::PAYMENT_METHOD_CASH) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['received_amount'] ?? null) ?? 0), 2);
                $received = $cashAmount;

                if ($received < $total) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Uang yang diterima kurang dari total belanja ('.number_format($total, 2).').',
                    ]);
                }

                $change = round($received - $total, 2);
            } elseif ($method === Sale::PAYMENT_METHOD_TRANSFER) {
                $cashAmount = 0;
                $transferAmount = $total;
                $received = $total;
                $change = 0;
            } elseif ($method === Sale::PAYMENT_METHOD_SPLIT) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = round($cashAmount + $transferAmount, 2);

                if ($received < $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Jumlah pembayaran (Tunai + Transfer) kurang dari total belanja ('.number_format($total, 2).').',
                    ]);
                }

                $change = round($received - $total, 2);
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_CASH) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = null;
                $received = $cashAmount;
                $change = 0;

                if ($cashAmount < 0) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Nominal uang muka tunai tidak boleh negatif.',
                    ]);
                }

                if ($cashAmount >= $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Uang muka tunai (Rp ' . number_format($cashAmount, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Tunai.',
                    ]);
                }
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_TRANSFER) {
                $cashAmount = null;
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = $transferAmount;
                $change = 0;

                if ($transferAmount < 0) {
                    throw ValidationException::withMessages([
                        'transfer_amount' => 'Nominal uang muka transfer tidak boleh negatif.',
                    ]);
                }

                if ($transferAmount >= $total) {
                    throw ValidationException::withMessages([
                        'transfer_amount' => 'Uang muka transfer (Rp ' . number_format($transferAmount, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Transfer.',
                    ]);
                }
            } elseif ($method === Sale::PAYMENT_METHOD_CREDIT_SPLIT) {
                $cashAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['cash_amount'] ?? null) ?? 0), 2);
                $transferAmount = round((float) (\App\Filament\Pages\KasirPage::parseNumericAmount($data['transfer_amount'] ?? null) ?? 0), 2);
                $received = round($cashAmount + $transferAmount, 2);
                $change = 0;

                if ($received < 0) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Nominal uang muka tidak boleh negatif.',
                    ]);
                }

                if ($received >= $total) {
                    throw ValidationException::withMessages([
                        'cash_amount' => 'Total uang muka (Rp ' . number_format($received, 0, ',', '.') . ') melebihi atau sama dengan total belanja. Gunakan metode Tunai + Transfer.',
                    ]);
                }
            } else {
                // Sale::PAYMENT_METHOD_RECEIVABLE
                $cashAmount = null;
                $transferAmount = null;
                $received = null;
                $change = null;
            }

            // 3. Deduct new stock and replace sale items
            $sale->items()->delete();

            $hasNotesCol = \Illuminate\Support\Facades\Schema::hasColumn('sale_items', 'notes');

            foreach ($lines as $line) {
                /** @var Product $prod */
                $prod = $line['product'];
                unset($line['product']);

                if (! $hasNotesCol) {
                    unset($line['notes']);
                }

                SaleItem::create(array_merge($line, ['sale_id' => $sale->id]));
                $prod->deductStock($line['quantity']);
            }

            // 4. Handle Receivable sync
            $oldReceivableId = $sale->receivable_id;
            $newReceivableId = $oldReceivableId;
            $isCredit = in_array($method, [
                Sale::PAYMENT_METHOD_RECEIVABLE,
                Sale::PAYMENT_METHOD_CREDIT_CASH,
                Sale::PAYMENT_METHOD_CREDIT_TRANSFER,
                Sale::PAYMENT_METHOD_CREDIT_SPLIT,
            ]);

            if ($isCredit) {
                $downPayment = round((float) ($cashAmount ?? 0) + (float) ($transferAmount ?? 0), 2);

                if ($oldReceivableId && $sale->receivable) {
                    // Update existing receivable
                    $receivable = $sale->receivable;
                    if ($receivable->receivable_party_id !== $party->id) {
                        $receivable->update(['receivable_party_id' => $party->id]);
                    }
                    app(ReceivableService::class)->updateReceivable($receivable, [
                        'amount' => $total,
                        'receivable_date' => $saleDate,
                        'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $receivable->due_date,
                        'description' => 'Penjualan kredit '.$sale->transaction_number,
                    ]);
                } else {
                    // Create new receivable
                    $receivable = app(ReceivableService::class)->createReceivable([
                        'receivable_party_id' => $party->id,
                        'amount' => $total,
                        'receivable_date' => $saleDate,
                        'due_date' => $data['due_date'] ?? null,
                        'description' => 'Penjualan kredit '.$sale->transaction_number,
                    ], $user);

                    if ($downPayment > 0) {
                        $dpType = $method === Sale::PAYMENT_METHOD_CREDIT_TRANSFER ? 'Transfer' : ($method === Sale::PAYMENT_METHOD_CREDIT_SPLIT ? 'Tunai & Transfer' : 'Tunai');
                        app(ReceivablePaymentService::class)->recordInstallment([
                            'receivable_id' => $receivable->id,
                            'amount' => $downPayment,
                            'installment_date' => $saleDate,
                            'description' => "Uang Muka Transaksi {$sale->transaction_number} ({$dpType})",
                        ], $user);
                    }

                    $newReceivableId = $receivable->id;
                }
            } else {
                // Not receivable anymore
                if ($oldReceivableId && $sale->receivable) {
                    $receivable = $sale->receivable;
                    if ($receivable->paymentHistories()->exists()) {
                        throw ValidationException::withMessages([
                            'payment_method' => 'Penjualan kredit ini sudah memiliki pembayaran piutang dan tidak dapat diubah ke metode non-kredit.',
                        ]);
                    }
                    app(ReceivableService::class)->deleteReceivable($receivable);
                    $newReceivableId = null;
                }
            }

            // 5. Update Sale record
            $sale->update([
                'sale_date' => $saleDate,
                'payment_method' => $method,
                'receivable_party_id' => $party->id,
                'receivable_id' => $newReceivableId,
                'total_amount' => $total,
                'cash_amount' => $cashAmount,
                'transfer_amount' => $transferAmount,
                'received_amount' => $received,
                'change_amount' => $change,
                'description' => $data['description'] ?? $sale->description,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                'Penjualan',
                'update',
                "Memperbarui transaksi penjualan {$sale->transaction_number} ({$sale->payment_method_label}) senilai Rp " . number_format($total, 0, ',', '.') . " untuk pelanggan {$party->name}",
                $sale,
                ['total' => $total, 'payment_method' => $method, 'party' => $party->name],
                $user
            );

            return $sale->fresh()->load(['items', 'party', 'creator', 'receivable']);
        });
    }

    /**
     * Membatalkan penjualan (khusus Admin).
     *
     * Nota piutang otomatis yang belum memiliki pembayaran ikut dihapus.
     * Nota piutang yang sudah menerima pembayaran tidak dapat dibatalkan.
     */
    public function deleteSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            if ($sale->receivable_id) {
                $receivable = $sale->receivable;

                if ($receivable && $receivable->paymentHistories()->exists()) {
                    throw ValidationException::withMessages([
                        'sale' => 'Penjualan kredit ini sudah menerima pembayaran piutang dan tidak dapat dibatalkan.',
                    ]);
                }

                if ($receivable) {
                    app(ReceivableService::class)->deleteReceivable($receivable);
                }
            }

            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->restoreStock($item->quantity);
                }
            }

            app(\App\Services\ActivityLogService::class)->log(
                'Penjualan',
                'delete',
                "Membatalkan nota penjualan {$sale->transaction_number} senilai Rp " . number_format((float)$sale->total_amount, 0, ',', '.'),
                $sale,
                ['transaction_number' => $sale->transaction_number, 'amount' => $sale->total_amount]
            );

            $sale->delete();
        });
    }
}

