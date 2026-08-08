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

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $lineTotal = round((float) $product->price * $quantity, 2);

                $total = round($total + $lineTotal, 2);

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
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

            if ($method === Sale::PAYMENT_METHOD_CASH) {
                $received = round((float) ($data['received_amount'] ?? 0), 2);

                if ($received < $total) {
                    throw ValidationException::withMessages([
                        'received_amount' => 'Uang yang diterima kurang dari total belanja ('.number_format($total, 2).').',
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
                'received_amount' => $received,
                'change_amount' => $change,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            foreach ($lines as $line) {
                SaleItem::create(array_merge($line, ['sale_id' => $sale->id]));
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

        $sale->delete();
    }
}
