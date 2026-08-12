<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public const TYPE_DEBT_LIST = 'debt_list';
    public const TYPE_PAYMENT_HISTORY = 'payment_history';
    public const TYPE_PAYMENT_PERIOD = 'payment_period';
    public const TYPE_UNPAID_DEBTS = 'unpaid_debts';
    public const TYPE_OVERDUE_CUSTOMERS = 'overdue_customers';

    public const TYPES = [
        self::TYPE_DEBT_LIST => 'Daftar Hutang Toko',
        self::TYPE_PAYMENT_HISTORY => 'Riwayat Pembayaran Hutang',
        self::TYPE_PAYMENT_PERIOD => 'Rekap Pembayaran Hutang per Periode',
        self::TYPE_UNPAID_DEBTS => 'Rekap Hutang Toko Belum Lunas',
        self::TYPE_OVERDUE_CUSTOMERS => 'Rekap Supplier Jatuh Tempo',
    ];

    /**
     * @param  array{type: string, from?: string, until?: string, customer_id?: int|null, status?: string|null, payment_type?: string|null}  $filters
     */
    public function data(array $filters): Collection
    {
        $type = $filters['type'] ?? self::TYPE_DEBT_LIST;

        return match ($type) {
            self::TYPE_PAYMENT_HISTORY => $this->paymentHistory($filters),
            self::TYPE_PAYMENT_PERIOD => $this->paymentPeriod($filters),
            self::TYPE_UNPAID_DEBTS => $this->unpaidDebts($filters),
            self::TYPE_OVERDUE_CUSTOMERS => $this->overdueCustomers($filters),
            default => $this->debtList($filters),
        };
    }

    private function debtList(array $filters): Collection
    {
        return Debt::query()
            ->with(['customer'])
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('debt_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('debt_date', '<=', $until))
            ->orderBy('debt_date')
            ->get()
            ->map(fn (Debt $debt) => [
                'invoice_number' => $debt->invoice_number,
                'customer' => $debt->customer?->name,
                'debt_date' => $debt->debt_date?->format('d M Y'),
                'amount' => $debt->amount,
                'paid_amount' => $debt->paid_amount,
                'remaining_amount' => $debt->remaining_amount,
                'status' => $debt->status_label,
            ]);
    }

    private function paymentHistory(array $filters): Collection
    {
        return PaymentHistory::query()
            ->with(['customer', 'debt', 'creator'])
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['payment_type'] ?? null, fn ($q, $type) => $q->where('payment_type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('payment_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('payment_date', '<=', $until))
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn (PaymentHistory $payment) => [
                'transaction_number' => $payment->transaction_number,
                'customer' => $payment->customer?->name,
                'invoice_number' => $payment->debt?->invoice_number,
                'payment_type' => $payment->payment_type_label,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date?->format('d M Y'),
                'creator' => $payment->creator?->name,
            ]);
    }

    private function paymentPeriod(array $filters): Collection
    {
        $rows = PaymentHistory::query()
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('payment_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('payment_date', '<=', $until))
            ->orderBy('payment_date')
            ->get()
            ->groupBy(fn (PaymentHistory $payment) => $payment->payment_date->format('Y-m-d'));

        return $rows->map(function (Collection $group, string $date) {
            return [
                'period' => Carbon::parse($date)->format('d M Y'),
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        })->values();
    }

    private function unpaidDebts(array $filters): Collection
    {
        return Debt::query()
            ->with(['customer'])
            ->where('status', Debt::STATUS_UNPAID)
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('debt_date', '>=', $from))
            ->when($filters['until'] ?? null, fn ($q, $until) => $q->where('debt_date', '<=', $until))
            ->orderBy('debt_date')
            ->get()
            ->map(fn (Debt $debt) => [
                'invoice_number' => $debt->invoice_number,
                'customer' => $debt->customer?->name,
                'debt_date' => $debt->debt_date?->format('d M Y'),
                'due_date' => $debt->due_date?->format('d M Y'),
                'amount' => $debt->amount,
                'paid_amount' => $debt->paid_amount,
                'remaining_amount' => $debt->remaining_amount,
            ]);
    }

    private function overdueCustomers(array $filters): Collection
    {
        return Customer::query()
            ->withCount([
                'debts as unpaid_count' => fn ($q) => $q->where('status', Debt::STATUS_UNPAID),
                'debts as overdue_count' => fn ($q) => $q->overdue(),
            ])
            ->withSum(['debts as remaining_sum' => fn ($q) => $q->where('status', Debt::STATUS_UNPAID)], 'remaining_amount')
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->orderByDesc('remaining_sum')
            ->get()
            ->filter(fn (Customer $customer) => (float) $customer->remaining_sum > 0)
            ->values()
            ->map(fn (Customer $customer) => [
                'customer' => $customer->name,
                'phone' => $customer->phone,
                'unpaid_count' => $customer->unpaid_count,
                'overdue_count' => $customer->overdue_count,
                'remaining_amount' => $customer->remaining_sum,
                'oldest_debt_date' => $customer->debts()
                    ->where('status', Debt::STATUS_UNPAID)
                    ->min('debt_date')
                    ? Carbon::parse($customer->debts()->where('status', Debt::STATUS_UNPAID)->min('debt_date'))->format('d M Y')
                    : '-',
            ]);
    }

    public function title(string $type): string
    {
        return self::TYPES[$type] ?? 'Laporan';
    }
}
