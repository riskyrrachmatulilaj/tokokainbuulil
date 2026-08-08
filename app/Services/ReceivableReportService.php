<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReceivableReportService
{
    public const TYPE_RECEIVABLE_LIST = 'receivable_list';
    public const TYPE_PAYMENT_HISTORY = 'payment_history';
    public const TYPE_PAYMENT_PERIOD = 'payment_period';
    public const TYPE_UNPAID_RECEIVABLES = 'unpaid_receivables';
    public const TYPE_OVERDUE_PARTIES = 'overdue_parties';

    public const TYPES = [
        self::TYPE_RECEIVABLE_LIST => 'Daftar Piutang',
        self::TYPE_PAYMENT_HISTORY => 'Riwayat Penerimaan',
        self::TYPE_PAYMENT_PERIOD => 'Rekap Penerimaan per Periode',
        self::TYPE_UNPAID_RECEIVABLES => 'Rekap Piutang Belum Lunas',
        self::TYPE_OVERDUE_PARTIES => 'Rekap Debitur Menunggak',
    ];

    /**
     * @param  array{type: string, from?: string, until?: string, party_id?: int|null, status?: string|null, payment_type?: string|null}  $filters
     */
    public function data(array $filters): Collection
    {
        $type = $filters['type'] ?? self::TYPE_RECEIVABLE_LIST;

        return match ($type) {
            self::TYPE_PAYMENT_HISTORY => $this->paymentHistory($filters),
            self::TYPE_PAYMENT_PERIOD => $this->paymentPeriod($filters),
            self::TYPE_UNPAID_RECEIVABLES => $this->unpaidReceivables($filters),
            self::TYPE_OVERDUE_PARTIES => $this->overdueParties($filters),
            default => $this->receivableList($filters),
        };
    }

    private function receivableList(array $filters): Collection
    {
        return Receivable::query()
            ->with(['party'])
            ->when($filters['party_id'] ?? null, fn ($q, $id) => $q->where('receivable_party_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('receivable_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('receivable_date', '<=', $until))
            ->orderBy('receivable_date')
            ->get()
            ->map(fn (Receivable $receivable) => [
                'invoice_number' => $receivable->invoice_number,
                'party' => $receivable->party?->name,
                'receivable_date' => $receivable->receivable_date?->format('d M Y'),
                'amount' => $receivable->amount,
                'paid_amount' => $receivable->paid_amount,
                'remaining_amount' => $receivable->remaining_amount,
                'status' => $receivable->status_label,
            ]);
    }

    private function paymentHistory(array $filters): Collection
    {
        return ReceivablePaymentHistory::query()
            ->with(['party', 'receivable', 'creator'])
            ->when($filters['party_id'] ?? null, fn ($q, $id) => $q->where('receivable_party_id', $id))
            ->when($filters['payment_type'] ?? null, fn ($q, $type) => $q->where('payment_type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('payment_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('payment_date', '<=', $until))
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn (ReceivablePaymentHistory $payment) => [
                'transaction_number' => $payment->transaction_number,
                'party' => $payment->party?->name,
                'invoice_number' => $payment->receivable?->invoice_number,
                'payment_type' => $payment->payment_type_label,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date?->format('d M Y'),
                'creator' => $payment->creator?->name,
            ]);
    }

    private function paymentPeriod(array $filters): Collection
    {
        $rows = ReceivablePaymentHistory::query()
            ->when($filters['party_id'] ?? null, fn ($q, $id) => $q->where('receivable_party_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('payment_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('payment_date', '<=', $until))
            ->orderBy('payment_date')
            ->get()
            ->groupBy(fn (ReceivablePaymentHistory $payment) => $payment->payment_date->format('Y-m-d'));

        return $rows->map(function (Collection $group, string $date) {
            return [
                'period' => Carbon::parse($date)->format('d M Y'),
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        })->values();
    }

    private function unpaidReceivables(array $filters): Collection
    {
        return Receivable::query()
            ->with(['party'])
            ->where('status', Receivable::STATUS_UNPAID)
            ->when($filters['party_id'] ?? null, fn ($q, $id) => $q->where('receivable_party_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('receivable_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('receivable_date', '<=', $until))
            ->orderBy('receivable_date')
            ->get()
            ->map(fn (Receivable $receivable) => [
                'invoice_number' => $receivable->invoice_number,
                'party' => $receivable->party?->name,
                'receivable_date' => $receivable->receivable_date?->format('d M Y'),
                'due_date' => $receivable->due_date?->format('d M Y'),
                'amount' => $receivable->amount,
                'paid_amount' => $receivable->paid_amount,
                'remaining_amount' => $receivable->remaining_amount,
            ]);
    }

    private function overdueParties(array $filters): Collection
    {
        return ReceivableParty::query()
            ->withCount([
                'receivables as unpaid_count' => fn ($q) => $q->where('status', Receivable::STATUS_UNPAID),
                'receivables as overdue_count' => fn ($q) => $q->overdue(),
            ])
            ->withSum(['receivables as remaining_sum' => fn ($q) => $q->where('status', Receivable::STATUS_UNPAID)], 'remaining_amount')
            ->when($filters['party_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->orderByDesc('remaining_sum')
            ->get()
            ->filter(fn (ReceivableParty $party) => (float) $party->remaining_sum > 0)
            ->values()
            ->map(fn (ReceivableParty $party) => [
                'party' => $party->name,
                'phone' => $party->phone,
                'unpaid_count' => $party->unpaid_count,
                'overdue_count' => $party->overdue_count,
                'remaining_amount' => $party->remaining_sum,
                'oldest_receivable_date' => $party->receivables()
                    ->where('status', Receivable::STATUS_UNPAID)
                    ->min('receivable_date')
                    ? Carbon::parse($party->receivables()->where('status', Receivable::STATUS_UNPAID)->min('receivable_date'))->format('d M Y')
                    : '-',
            ]);
    }

    public function title(string $type): string
    {
        return self::TYPES[$type] ?? 'Laporan';
    }
}
