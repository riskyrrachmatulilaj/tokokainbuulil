<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleThermalService
{
    public static function nota(Sale $sale): BinaryFileResponse
    {
        $sale->load(['items', 'party', 'creator']);

        $html = view('reports.sale-thermal', [
            'sale' => $sale,
            'printedAt' => now()->format('d M Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Struk-'.$sale->transaction_number.'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'thermal_').'.pdf';

        $itemCount = $sale->items->count();
        $heightMm = max(120, 100 + ($itemCount * 10));
        $heightPt = $heightMm * 2.83465;
        $widthPt = 204.1;

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper([0, 0, $widthPt, $heightPt])
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    /**
     * Membuat PDF nota penjualan format struk thermal (72mm) yang disajikan langsung di browser.
     */
    public static function notaInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator']);

        $html = view('reports.sale-thermal', [
            'sale' => $sale,
            'printedAt' => now()->format('d M Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $itemCount = $sale->items->count();
        $heightMm = max(120, 100 + ($itemCount * 10));
        $heightPt = $heightMm * 2.83465;
        $widthPt = 204.1;

        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, $widthPt, $heightPt]);
        return $pdf->stream('Struk-'.$sale->transaction_number.'.pdf');
    }
}
