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

            foreach ($lines as $line) {
                /** @var Product $prod */
                $prod = $line['product'];
                unset($line['product']);

                SaleItem::create(array_merge($line, ['sale_id' => $sale->id]));
                $prod->deductStock($line['quantity']);
            }

            if ($method === Sale::PAYMENT_METHOD_RECEIVABLE) {
                $receivable = app(ReceivableService::class)->createReceivable([
                    'receivable_party_id' => $party->id,
                    'amount' => $total,
                    'receivable_date' => $saleDate,
                    'due_date' => null,
                    'description' => 'Penjualan kredit '.$sale->transaction_number,
                ], $user);

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
