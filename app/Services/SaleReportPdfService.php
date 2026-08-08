<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReportPdfService
{
    public static function generate(string $date): BinaryFileResponse
    {
        $report = app(SaleReportService::class)->data($date);

        $html = view('reports.sale-daily-report', [
            'report' => $report,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $slug = Carbon::parse($date)->format('Ymd');
        $filename = 'Laporan-Penjualan-Harian-'.$slug.'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'sreport_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
