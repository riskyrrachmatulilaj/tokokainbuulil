<?php

namespace App\Services;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportPdfService
{
    public static function generate(string $type, array $filters): BinaryFileResponse
    {
        $service = app(ReportService::class);
        $rows = $service->data($filters);
        $title = $service->title($type);

        $html = view('reports.pdf', [
            'title' => $title,
            'period' => self::periodLabel($filters),
            'columns' => ReportExport::columnsFor($type),
            'rows' => $rows,
            'generatedAt' => now()->format('d M Y H:i'),
            'generatedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Laporan-'.Str::slug($title).'-'.now()->format('Ymd-His').'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'report_').'.pdf';

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
            return \Illuminate\Support\Carbon::parse($from)->format('d M Y').' s/d '.\Illuminate\Support\Carbon::parse($until)->format('d M Y');
        }

        if ($from) {
            return 'Mulai '.\Illuminate\Support\Carbon::parse($from)->format('d M Y');
        }

        if ($until) {
            return 'Sampai '.\Illuminate\Support\Carbon::parse($until)->format('d M Y');
        }

        return 'Semua Periode';
    }
}
