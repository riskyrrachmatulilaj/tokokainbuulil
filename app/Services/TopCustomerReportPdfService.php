<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopCustomerReportPdfService
{
    public static function generate(array $filters = []): BinaryFileResponse
    {
        $report = app(TopCustomerReportService::class)->data($filters);

        $html = view('reports.top-customer-report-pdf', [
            'report' => $report,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? 'Admin',
        ])->render();

        $filename = 'Laporan-Pelanggan-Terbanyak-' . Carbon::now()->format('Ymd-His') . '.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'topcust_') . '.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
