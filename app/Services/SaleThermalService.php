<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleThermalService
{
    /**
     * Dimensi kertas continuous: Lebar 12cm (120mm) x Tinggi 14cm (140mm) per lembar
     */
    public const PAPER_WIDTH_PT = 340.16;   // 120mm * 2.83465
    public const PAPER_HEIGHT_PT = 396.85;  // 140mm * 2.83465

    public static function nota(Sale $sale): BinaryFileResponse
    {
        $sale->load(['items', 'party', 'creator', 'receivable']);

        $html = view('reports.sale-thermal', [
            'sale' => $sale,
            'printedAt' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Struk-'.$sale->transaction_number.'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'thermal_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper([0, 0, self::PAPER_WIDTH_PT, self::PAPER_HEIGHT_PT], 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    /**
     * Membuat PDF nota penjualan format continuous (12cm x 14cm) yang disajikan langsung di browser.
     */
    public static function notaInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator', 'receivable']);

        $html = view('reports.sale-thermal', [
            'sale' => $sale,
            'printedAt' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, self::PAPER_WIDTH_PT, self::PAPER_HEIGHT_PT], 'portrait');
        return $pdf->stream('Struk-'.$sale->transaction_number.'.pdf');
    }
}
