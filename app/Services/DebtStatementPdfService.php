<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DebtStatementPdfService
{
    public static function generate(Customer $customer): BinaryFileResponse
    {
        $debts = $customer->debts()
            ->with(['paymentHistories' => fn ($query) => $query->orderBy('payment_date')])
            ->orderBy('debt_date')
            ->get();

        $totalAmount = (float) $debts->sum('amount');
        $totalPaid = (float) $debts->sum('paid_amount');
        $totalRemaining = (float) $debts->sum('remaining_amount');

        $summary = [
            'total_notes' => $debts->count(),
            'unpaid_notes' => $debts->where('status', Debt::STATUS_UNPAID)->count(),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
        ];

        $html = view('reports.debt-statement', [
            'customer' => $customer,
            'debts' => $debts,
            'summary' => $summary,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Rincian-Hutang-'.Str::slug($customer->name).'-'.now()->format('Ymd-His').'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'statement_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
