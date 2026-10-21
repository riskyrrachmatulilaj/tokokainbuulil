<?php

namespace App\Services;

use App\Models\DataChangeRequest;
use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataChangeRequestService
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly DebtService $debtService,
        private readonly ReceivableService $receivableService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    /**
     * Mengajukan permohonan perubahan atau pembatalan transaksi penjualan oleh kasir.
     */
    public function createSaleChangeRequest(
        Sale $sale,
        User $cashier,
        string $requestType,
        string $reason,
        ?array $proposedData = null
    ): DataChangeRequest {
        if ($sale->hasPendingChangeRequest()) {
            throw ValidationException::withMessages([
                'sale' => 'Transaksi ini sudah memiliki pengajuan perubahan yang sedang menunggu persetujuan admin.',
            ]);
        }

        if ($requestType === DataChangeRequest::TYPE_VOID) {
            if ($sale->isCredit() && $sale->receivable?->paymentHistories()->exists()) {
                throw ValidationException::withMessages([
                    'sale' => 'Penjualan kredit ini sudah menerima pembayaran piutang dan tidak dapat dibatalkan.',
                ]);
            }
        }

        if ($requestType === DataChangeRequest::TYPE_EDIT) {
            if (empty($proposedData['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Rincian produk perubahan tidak boleh kosong.',
                ]);
            }
        }

        return DB::transaction(function () use ($sale, $cashier, $requestType, $reason, $proposedData) {
            $sale->load(['items.product', 'party', 'receivable']);

            $originalData = [
                'transaction_number' => $sale->transaction_number,
                'sale_date' => $sale->sale_date?->format('Y-m-d'),
                'receivable_party_id' => $sale->receivable_party_id,
                'party_name' => $sale->party?->name ?? '-',
                'payment_method' => $sale->payment_method,
                'payment_method_label' => $sale->payment_method_label,
                'total_amount' => (float) $sale->total_amount,
                'cash_amount' => (float) $sale->cash_amount,
                'transfer_amount' => (float) $sale->transfer_amount,
                'received_amount' => (float) $sale->received_amount,
                'change_amount' => (float) $sale->change_amount,
                'description' => $sale->description,
                'items' => $sale->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name ?? $item->product?->name ?? 'Produk #' . $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'price' => (float) $item->price,
                    'notes' => $item->notes,
                    'subtotal' => (float) $item->subtotal,
                ])->toArray(),
            ];

            $changeRequest = DataChangeRequest::create([
                'user_id' => $cashier->id,
                'requestable_type' => Sale::class,
                'requestable_id' => $sale->id,
                'request_type' => $requestType,
                'reason' => trim($reason),
                'original_data' => $originalData,
                'proposed_data' => $proposedData,
                'status' => DataChangeRequest::STATUS_PENDING,
            ]);

            $typeLabel = $requestType === DataChangeRequest::TYPE_VOID ? 'pembatalan' : 'perubahan data';

            $this->activityLogService->log(
                'Persetujuan Data',
                'create',
                "Kasir {$cashier->name} mengajukan {$typeLabel} untuk nota {$sale->transaction_number}: \"{$reason}\"",
                $changeRequest,
                [
                    'sale_id' => $sale->id,
                    'transaction_number' => $sale->transaction_number,
                    'request_type' => $requestType,
                ],
                $cashier
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('role', User::ROLE_ADMIN)->get();
            foreach ($admins as $admin) {
                $notif = Notification::make()
                    ->warning()
                    ->title("Pengajuan {$changeRequest->request_type_label} Penjualan")
                    ->body("Kasir {$cashier->name} mengajukan {$typeLabel} nota {$sale->transaction_number}.");
                $this->sendDatabaseNotificationSafe($notif, $admin);
            }

            return $changeRequest;
        });
    }

    /**
     * Mengajukan permohonan perubahan atau pembatalan nota hutang toko oleh kasir/staf.
     */
    public function createDebtChangeRequest(
        Debt $debt,
        User $cashier,
        string $requestType,
        string $reason,
        ?array $proposedData = null
    ): DataChangeRequest {
        if ($debt->hasPendingChangeRequest()) {
            throw ValidationException::withMessages([
                'debt' => 'Nota hutang ini sudah memiliki pengajuan perubahan yang sedang menunggu persetujuan admin.',
            ]);
        }

        if ($requestType === DataChangeRequest::TYPE_VOID) {
            if ($debt->paymentHistories()->exists()) {
                throw ValidationException::withMessages([
                    'debt' => 'Nota hutang ini sudah memiliki riwayat pembayaran cicilan dan tidak dapat dibatalkan.',
                ]);
            }
        }

        if ($requestType === DataChangeRequest::TYPE_EDIT) {
            if (! isset($proposedData['amount']) || (float) $proposedData['amount'] <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal hutang tidak boleh kosong atau bernilai 0.',
                ]);
            }

            if ((float) $proposedData['amount'] < (float) $debt->paid_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal hutang baru tidak boleh lebih kecil dari total yang sudah dibayar (Rp ' . number_format((float) $debt->paid_amount, 0, ',', '.') . ').',
                ]);
            }

            if (empty($proposedData['customer_id'])) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Supplier / pihak terkait wajib dipilih.',
                ]);
            }

            if (empty($proposedData['debt_date'])) {
                throw ValidationException::withMessages([
                    'debt_date' => 'Tanggal hutang wajib diisi.',
                ]);
            }
        }

        return DB::transaction(function () use ($debt, $cashier, $requestType, $reason, $proposedData) {
            $debt->load('customer');

            $originalData = [
                'invoice_number' => $debt->invoice_number,
                'customer_id' => $debt->customer_id,
                'customer_name' => $debt->customer?->name ?? '-',
                'amount' => (float) $debt->amount,
                'paid_amount' => (float) $debt->paid_amount,
                'remaining_amount' => (float) $debt->remaining_amount,
                'debt_date' => $debt->debt_date?->format('Y-m-d'),
                'due_date' => $debt->due_date?->format('Y-m-d'),
                'description' => $debt->description,
            ];

            $changeRequest = DataChangeRequest::create([
                'user_id' => $cashier->id,
                'requestable_type' => Debt::class,
                'requestable_id' => $debt->id,
                'request_type' => $requestType,
                'reason' => trim($reason),
                'original_data' => $originalData,
                'proposed_data' => $proposedData,
                'status' => DataChangeRequest::STATUS_PENDING,
            ]);

            $typeLabel = $requestType === DataChangeRequest::TYPE_VOID ? 'pembatalan' : 'perubahan data';

            $this->activityLogService->log(
                'Persetujuan Data',
                'create',
                "Kasir {$cashier->name} mengajukan {$typeLabel} untuk nota hutang {$debt->invoice_number}: \"{$reason}\"",
                $changeRequest,
                [
                    'debt_id' => $debt->id,
                    'invoice_number' => $debt->invoice_number,
                    'request_type' => $requestType,
                ],
                $cashier
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('role', User::ROLE_ADMIN)->get();
            foreach ($admins as $admin) {
                $notif = Notification::make()
                    ->warning()
                    ->title("Pengajuan {$changeRequest->request_type_label} Hutang")
                    ->body("Kasir {$cashier->name} mengajukan {$typeLabel} nota hutang {$debt->invoice_number}.");
                $this->sendDatabaseNotificationSafe($notif, $admin);
            }

            return $changeRequest;
        });
    }

    /**
     * Mengajukan permohonan perubahan atau pembatalan nota piutang oleh kasir/staf.
     */
    public function createReceivableChangeRequest(
        Receivable $receivable,
        User $cashier,
        string $requestType,
        string $reason,
        ?array $proposedData = null
    ): DataChangeRequest {
        if ($receivable->hasPendingChangeRequest()) {
            throw ValidationException::withMessages([
                'receivable' => 'Nota piutang ini sudah memiliki pengajuan perubahan yang sedang menunggu persetujuan admin.',
            ]);
        }

        if ($receivable->sale()->exists()) {
            $sale = $receivable->sale;
            throw ValidationException::withMessages([
                'receivable' => "Nota piutang ini terhubung dengan Penjualan {$sale->transaction_number}. Silakan ajukan perubahan melalui menu Transaksi Penjualan.",
            ]);
        }

        if ($requestType === DataChangeRequest::TYPE_VOID) {
            if ($receivable->paymentHistories()->exists()) {
                throw ValidationException::withMessages([
                    'receivable' => 'Nota piutang ini sudah memiliki riwayat pembayaran cicilan dan tidak dapat dibatalkan.',
                ]);
            }
        }

        if ($requestType === DataChangeRequest::TYPE_EDIT) {
            if (! isset($proposedData['amount']) || (float) $proposedData['amount'] <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal piutang tidak boleh kosong atau bernilai 0.',
                ]);
            }

            if ((float) $proposedData['amount'] < (float) $receivable->paid_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal piutang baru tidak boleh lebih kecil dari total yang sudah diterima (Rp ' . number_format((float) $receivable->paid_amount, 0, ',', '.') . ').',
                ]);
            }

            if (empty($proposedData['receivable_party_id'])) {
                throw ValidationException::withMessages([
                    'receivable_party_id' => 'Pelanggan / debitur wajib dipilih.',
                ]);
            }

            if (empty($proposedData['receivable_date'])) {
                throw ValidationException::withMessages([
                    'receivable_date' => 'Tanggal piutang wajib diisi.',
                ]);
            }
        }

        return DB::transaction(function () use ($receivable, $cashier, $requestType, $reason, $proposedData) {
            $receivable->load('party');

            $originalData = [
                'invoice_number' => $receivable->invoice_number,
                'receivable_party_id' => $receivable->receivable_party_id,
                'party_name' => $receivable->party?->name ?? '-',
                'amount' => (float) $receivable->amount,
                'paid_amount' => (float) $receivable->paid_amount,
                'remaining_amount' => (float) $receivable->remaining_amount,
                'receivable_date' => $receivable->receivable_date?->format('Y-m-d'),
                'due_date' => $receivable->due_date?->format('Y-m-d'),
                'description' => $receivable->description,
            ];

            $changeRequest = DataChangeRequest::create([
                'user_id' => $cashier->id,
                'requestable_type' => Receivable::class,
                'requestable_id' => $receivable->id,
                'request_type' => $requestType,
                'reason' => trim($reason),
                'original_data' => $originalData,
                'proposed_data' => $proposedData,
                'status' => DataChangeRequest::STATUS_PENDING,
            ]);

            $typeLabel = $requestType === DataChangeRequest::TYPE_VOID ? 'pembatalan' : 'perubahan data';

            $this->activityLogService->log(
                'Persetujuan Data',
                'create',
                "Kasir {$cashier->name} mengajukan {$typeLabel} untuk nota piutang {$receivable->invoice_number}: \"{$reason}\"",
                $changeRequest,
                [
                    'receivable_id' => $receivable->id,
                    'invoice_number' => $receivable->invoice_number,
                    'request_type' => $requestType,
                ],
                $cashier
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('role', User::ROLE_ADMIN)->get();
            foreach ($admins as $admin) {
                $notif = Notification::make()
                    ->warning()
                    ->title("Pengajuan {$changeRequest->request_type_label} Piutang")
                    ->body("Kasir {$cashier->name} mengajukan {$typeLabel} nota piutang {$receivable->invoice_number}.");
                $this->sendDatabaseNotificationSafe($notif, $admin);
            }

            return $changeRequest;
        });
    }

    /**
     * Menyetujui pengajuan perubahan data oleh admin.
     */
    public function approveRequest(DataChangeRequest $request, User $admin): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah pernah diproses sebelumnya.',
            ]);
        }

        DB::transaction(function () use ($request, $admin) {
            $requestable = $request->requestable;

            if ($requestable instanceof Sale) {
                if ($request->request_type === DataChangeRequest::TYPE_VOID) {
                    $this->saleService->deleteSale($requestable);
                } elseif ($request->request_type === DataChangeRequest::TYPE_EDIT) {
                    $this->saleService->updateSale($requestable, $request->proposed_data ?? [], $admin);
                }
            } elseif ($requestable instanceof Debt) {
                if ($request->request_type === DataChangeRequest::TYPE_VOID) {
                    $this->debtService->deleteDebt($requestable);
                } elseif ($request->request_type === DataChangeRequest::TYPE_EDIT) {
                    $this->debtService->updateDebt($requestable, $request->proposed_data ?? []);
                }
            } elseif ($requestable instanceof Receivable) {
                if ($request->request_type === DataChangeRequest::TYPE_VOID) {
                    $this->receivableService->deleteReceivable($requestable);
                } elseif ($request->request_type === DataChangeRequest::TYPE_EDIT) {
                    $this->receivableService->updateReceivable($requestable, $request->proposed_data ?? []);
                }
            }

            $request->update([
                'status' => DataChangeRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $docNumber = $request->document_number;

            $this->activityLogService->log(
                'Persetujuan Data',
                'approve',
                "Admin {$admin->name} menyetujui pengajuan {$request->request_type_label} nota {$docNumber}",
                $request,
                ['request_id' => $request->id],
                $admin
            );

            // Notifikasi ke Kasir pengaju
            if ($request->user) {
                $notif = Notification::make()
                    ->success()
                    ->title("Pengajuan {$request->request_type_label} Disetujui")
                    ->body("Permohonan {$request->request_type_label} nota {$docNumber} telah disetujui oleh admin {$admin->name}.");
                $this->sendDatabaseNotificationSafe($notif, $request->user);
            }
        });
    }

    /**
     * Menolak pengajuan perubahan data oleh admin.
     */
    public function rejectRequest(DataChangeRequest $request, User $admin, string $rejectionReason): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah pernah diproses sebelumnya.',
            ]);
        }

        if (trim($rejectionReason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($request, $admin, $rejectionReason) {
            $request->update([
                'status' => DataChangeRequest::STATUS_REJECTED,
                'rejection_reason' => trim($rejectionReason),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $docNumber = $request->document_number;

            $this->activityLogService->log(
                'Persetujuan Data',
                'reject',
                "Admin {$admin->name} menolak pengajuan {$request->request_type_label} nota {$docNumber}: \"{$rejectionReason}\"",
                $request,
                ['request_id' => $request->id, 'reason' => $rejectionReason],
                $admin
            );

            // Notifikasi ke Kasir pengaju
            if ($request->user) {
                $notif = Notification::make()
                    ->danger()
                    ->title("Pengajuan {$request->request_type_label} Ditolak")
                    ->body("Permohonan {$request->request_type_label} nota {$docNumber} ditolak oleh admin. Alasan: {$rejectionReason}");
                $this->sendDatabaseNotificationSafe($notif, $request->user);
            }
        });
    }

    private function sendDatabaseNotificationSafe(Notification $notification, ?User $user): void
    {
        if (! $user) {
            return;
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                $notification->sendToDatabase($user);
            }
        } catch (\Throwable) {
            // Abaikan jika tabel notifikasi belum termigrasi
        }
    }
}
