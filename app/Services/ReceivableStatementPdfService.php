<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceivableStatementPdfService
{
    public static function generate(ReceivableParty $party): BinaryFileResponse
    {
        $receivables = $party->receivables()
            ->with(['paymentHistories' => fn ($query) => $query->orderBy('payment_date')])
            ->orderBy('receivable_date')
            ->get();

        $totalAmount = (float) $receivables->sum('amount');
        $totalPaid = (float) $receivables->sum('paid_amount');
        $totalRemaining = (float) $receivables->sum('remaining_amount');

        $summary = [
            'total_notes' => $receivables->count(),
            'unpaid_notes' => $receivables->where('status', Receivable::STATUS_UNPAID)->count(),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
        ];

        $html = view('reports.receivable-statement', [
            'party' => $party,
            'receivables' => $receivables,
            'summary' => $summary,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Rincian-Piutang-'.Str::slug($party->name).'-'.now()->format('Ymd-His').'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'rstatement_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
