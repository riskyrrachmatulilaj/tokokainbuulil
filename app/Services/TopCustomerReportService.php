<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TopCustomerReportService
{
    public const SORT_TRANSACTIONS = 'transactions';
    public const SORT_REVENUE = 'revenue';
    public const SORT_QUANTITY = 'quantity';
    public const SORT_LAST_SALE = 'last_sale';

    public const SORTS = [
        self::SORT_TRANSACTIONS => 'Frekuensi Transaksi (Jumlah Nota)',
        self::SORT_REVENUE => 'Total Nominal Belanja (Omset Rp)',
        self::SORT_QUANTITY => 'Total Kuantitas Barang',
        self::SORT_LAST_SALE => 'Transaksi Terakhir',
    ];

    public const PRESET_THIS_MONTH = 'this_month';
    public const PRESET_LAST_MONTH = 'last_month';
    public const PRESET_THIS_YEAR = 'this_year';
    public const PRESET_LAST_30_DAYS = 'last_30_days';
    public const PRESET_ALL = 'all';
    public const PRESET_CUSTOM = 'custom';

    public const PRESETS = [
        self::PRESET_THIS_MONTH => 'Bulan Ini',
        self::PRESET_LAST_MONTH => 'Bulan Lalu',
        self::PRESET_LAST_30_DAYS => '30 Hari Terakhir',
        self::PRESET_THIS_YEAR => 'Tahun Ini',
        self::PRESET_ALL => 'Semua Waktu',
        self::PRESET_CUSTOM => 'Pilih Tanggal Manual',
    ];

    /**
     * Mengolah data laporan pelanggan dengan transaksi terbanyak.
     *
     * @param array{
     *     preset?: string,
     *     from?: ?string,
     *     until?: ?string,
     *     sort_by?: string,
     *     limit?: int|string,
     *     search?: ?string
     * } $filters
     */
    public function data(array $filters = []): array
    {
        [$from, $until, $periodLabel] = $this->resolveDateRange($filters);

        $sortBy = $filters['sort_by'] ?? self::SORT_TRANSACTIONS;
        if (! array_key_exists($sortBy, self::SORTS)) {
            $sortBy = self::SORT_TRANSACTIONS;
        }

        $limit = $filters['limit'] ?? 25;
        $search = trim((string) ($filters['search'] ?? ''));

        $salesQuery = Sale::query()
            ->when($from, fn ($q) => $q->whereDate('sale_date', '>=', $from))
            ->when($until, fn ($q) => $q->whereDate('sale_date', '<=', $until));

        $allSales = (clone $salesQuery)
            ->with(['party' => fn ($q) => $q->withTrashed(), 'items:id,sale_id,quantity'])
            ->get();

        $walkInSales = $allSales->whereNull('receivable_party_id');
        $walkInTxns = $walkInSales->count();
        $walkInRevenue = (float) $walkInSales->sum('total_amount');
        $walkInQty = (float) $walkInSales->sum(fn (Sale $s) => $s->items->sum('quantity'));

        $customerSales = $allSales->whereNotNull('receivable_party_id');
        $groupedSales = $customerSales->groupBy('receivable_party_id');

        $partyIds = $groupedSales->keys()->all();
        $unpaidReceivables = Receivable::query()
            ->whereIn('receivable_party_id', $partyIds)
            ->where('status', Receivable::STATUS_UNPAID)
            ->selectRaw('receivable_party_id, SUM(remaining_amount) as total_unpaid')
            ->groupBy('receivable_party_id')
            ->pluck('total_unpaid', 'receivable_party_id');

        $customerRows = collect();

        foreach ($groupedSales as $partyId => $sales) {
            /** @var ReceivableParty|null $party */
            $party = $sales->first()->party;
            $name = $party?->name ?? 'Pelanggan #' . $partyId;
            $phone = $party?->phone;
            $address = $party?->address;

            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $nameMatch = str_contains(mb_strtolower($name), $searchLower);
                $phoneMatch = $phone ? str_contains(mb_strtolower($phone), $searchLower) : false;
                $addressMatch = $address ? str_contains(mb_strtolower($address), $searchLower) : false;

                if (! $nameMatch && ! $phoneMatch && ! $addressMatch) {
                    continue;
                }
            }

            $txnCount = $sales->count();
            $totalSpent = (float) $sales->sum('total_amount');
            $totalQty = (float) $sales->sum(fn (Sale $s) => $s->items->sum('quantity'));
            $avgSpent = $txnCount > 0 ? round($totalSpent / $txnCount, 2) : 0.0;
            $latestSale = $sales->sortByDesc('sale_date')->first();
            $lastSaleDate = $latestSale?->sale_date;
            $unpaidAmount = (float) ($unpaidReceivables[$partyId] ?? 0);

            $customerRows->push([
                'party_id' => $partyId,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'is_deleted' => $party?->trashed() ?? false,
                'transaction_count' => $txnCount,
                'total_spent' => round($totalSpent, 2),
                'total_quantity' => $totalQty,
                'average_spent' => $avgSpent,
                'last_sale_date' => $lastSaleDate ? Carbon::parse($lastSaleDate)->format('Y-m-d') : null,
                'last_sale_date_formatted' => $lastSaleDate ? Carbon::parse($lastSaleDate)->format('d M Y') : '-',
                'unpaid_receivable' => round($unpaidAmount, 2),
            ]);
        }

        $sortedRows = match ($sortBy) {
            self::SORT_REVENUE => $customerRows->sortByDesc('total_spent')->values(),
            self::SORT_QUANTITY => $customerRows->sortByDesc('total_quantity')->values(),
            self::SORT_LAST_SALE => $customerRows->sortByDesc('last_sale_date')->values(),
            default => $customerRows->sortByDesc('transaction_count')->values(),
        };

        $totalActiveCustomers = $sortedRows->count();
        $totalCustomerTxns = (int) $sortedRows->sum('transaction_count');
        $totalCustomerRevenue = round((float) $sortedRows->sum('total_spent'), 2);
        $totalCustomerQty = (float) $sortedRows->sum('total_quantity');

        if ($limit !== 'all' && is_numeric($limit) && (int) $limit > 0) {
            $displayedRows = $sortedRows->take((int) $limit);
        } else {
            $displayedRows = $sortedRows;
        }

        $rankedCustomers = $displayedRows->map(function ($row, $index) {
            $row['rank'] = $index + 1;
            return $row;
        });

        $grandTotalTxns = $walkInTxns + $totalCustomerTxns;
        $grandTotalRevenue = round($walkInRevenue + $totalCustomerRevenue, 2);
        $grandTotalQty = $walkInQty + $totalCustomerQty;

        return [
            'filters' => [
                'from' => $from,
                'until' => $until,
                'period_label' => $periodLabel,
                'sort_by' => $sortBy,
                'sort_label' => self::SORTS[$sortBy] ?? self::SORTS[self::SORT_TRANSACTIONS],
                'limit' => $limit,
                'search' => $search,
            ],
            'summary' => [
                'active_customers_count' => $totalActiveCustomers,
                'customer_transactions' => $totalCustomerTxns,
                'customer_revenue' => $totalCustomerRevenue,
                'customer_quantity' => $totalCustomerQty,
                'walk_in_transactions' => $walkInTxns,
                'walk_in_revenue' => round($walkInRevenue, 2),
                'walk_in_quantity' => $walkInQty,
                'grand_total_transactions' => $grandTotalTxns,
                'grand_total_revenue' => $grandTotalRevenue,
                'grand_total_quantity' => $grandTotalQty,
            ],
            'customers' => $rankedCustomers,
        ];
    }

    /**
     * Menentukan rentang tanggal dan label periode berdasarkan filter.
     */
    protected function resolveDateRange(array $filters): array
    {
        $preset = $filters['preset'] ?? null;

        if (! $preset) {
            if (! empty($filters['from']) || ! empty($filters['until'])) {
                $preset = self::PRESET_CUSTOM;
            } else {
                $preset = self::PRESET_THIS_MONTH;
            }
        }

        return match ($preset) {
            self::PRESET_LAST_MONTH => [
                Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
                Carbon::now()->subMonth()->translatedFormat('F Y'),
            ],
            self::PRESET_LAST_30_DAYS => [
                Carbon::now()->subDays(29)->format('Y-m-d'),
                Carbon::now()->format('Y-m-d'),
                '30 Hari Terakhir (' . Carbon::now()->subDays(29)->format('d M') . ' sampai ' . Carbon::now()->format('d M Y') . ')',
            ],
            self::PRESET_THIS_YEAR => [
                Carbon::now()->startOfYear()->format('Y-m-d'),
                Carbon::now()->endOfYear()->format('Y-m-d'),
                'Tahun ' . Carbon::now()->format('Y'),
            ],
            self::PRESET_ALL => [
                null,
                null,
                'Semua Waktu',
            ],
            self::PRESET_CUSTOM => $this->formatCustomRange($filters['from'] ?? null, $filters['until'] ?? null),
            default => [
                Carbon::now()->startOfMonth()->format('Y-m-d'),
                Carbon::now()->endOfMonth()->format('Y-m-d'),
                Carbon::now()->translatedFormat('F Y'),
            ],
        };
    }

    protected function formatCustomRange(?string $from, ?string $until): array
    {
        if ($from && $until) {
            $label = Carbon::parse($from)->format('d M Y') . ' sampai ' . Carbon::parse($until)->format('d M Y');
        } elseif ($from) {
            $label = 'Sejak ' . Carbon::parse($from)->format('d M Y');
        } elseif ($until) {
            $label = 'Sampai ' . Carbon::parse($until)->format('d M Y');
        } else {
            $label = 'Semua Waktu';
        }

        return [$from, $until, $label];
    }
}
