<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleThermalService
{
    /**
     * Membuat PDF nota penjualan format struk thermal (80mm).
     */
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

        // 80mm = 226.77 points, tinggi dinamis berdasarkan jumlah item
        $itemCount = $sale->items->count();
        $heightMm = 120 + ($itemCount * 8); // base height + per-item height
        $heightMm = max($heightMm, 120);    // minimum height
        $heightPt = $heightMm * 2.83465;    // mm to points
        $widthPt = 226.77;                  // 80mm in points

        file_put_contents($tempFile, Pdf::loadHTML($html)
            ->setPaper([0, 0, $widthPt, $heightPt])
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
