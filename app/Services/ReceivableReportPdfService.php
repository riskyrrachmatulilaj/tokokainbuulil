<?php

namespace App\Services;

use App\Exports\ReceivableReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceivableReportPdfService
{
    public static function generate(string $type, array $filters): BinaryFileResponse
    {
        $service = app(ReceivableReportService::class);
        $rows = $service->data($filters);
        $title = $service->title($type);

        $html = view('reports.pdf', [
            'title' => $title,
            'period' => self::periodLabel($filters),
            'columns' => ReceivableReportExport::columnsFor($type),
            'rows' => $rows,
            'exporter' => ReceivableReportExport::class,
            'type' => $type,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Laporan-'.Str::slug($title).'-'.now()->format('Ymd-His').'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'rreport_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    private static function periodLabel(array $filters): string
    {
        $from = $filters['from'] ?? null;
        $until = $filters['until'] ?? null;

        if ($from && $until) {
            return Carbon::parse($from)->format('d M Y').' s/d '.Carbon::parse($until)->format('d M Y');
        }

        if ($from) {
            return 'Mulai '.Carbon::parse($from)->format('d M Y');
        }

        if ($until) {
            return 'Sampai '.Carbon::parse($until)->format('d M Y');
        }

        return 'Semua Periode';
    }
}
