<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalePdfService
{
    public static function nota(Sale $sale): BinaryFileResponse
    {
        $sale->load(['items', 'party', 'creator']);

        $html = view('reports.sale-nota', [
            'sale' => $sale,
            'printedAt' => now()->format('d M Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $filename = 'Nota-'.$sale->transaction_number.'.pdf';
        $tempFile = tempnam(sys_get_temp_dir(), 'nota_').'.pdf';

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    /**
     * Membuat PDF nota penjualan (struk kasir) yang disajikan langsung di browser (preview).
     */
    public static function notaInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator']);

        $html = view('reports.sale-nota', [
            'sale' => $sale,
            'printedAt' => now()->format('d M Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->stream('Nota-'.$sale->transaction_number.'.pdf');
    }
}
