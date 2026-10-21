<?php

namespace App\Services;

use App\Models\DataChangeRequest;
use App\Models\Sale;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataChangeRequestService
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    /**
     * Mengajukan permohonan perubahan atau pembatalan transaksi oleh kasir.
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
                    ->title("Pengajuan {$changeRequest->request_type_label} Baru")
                    ->body("Kasir {$cashier->name} mengajukan {$typeLabel} nota {$sale->transaction_number}.");
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
            }

            $request->update([
                'status' => DataChangeRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $docNumber = $request->original_data['transaction_number'] ?? "#{$request->requestable_id}";

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

            $docNumber = $request->original_data['transaction_number'] ?? "#{$request->requestable_id}";

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
