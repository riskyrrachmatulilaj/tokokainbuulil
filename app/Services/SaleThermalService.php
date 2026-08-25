<?php

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleThermalService
{
    /**
     * Dimensi area cetak kertas continuous (12cm total lebar dengan lubang, 9.5cm area cetak x 14cm tinggi per lembar)
     */
    public const CONTINUOUS_WIDTH_PT = 340.16;   // 120mm * 2.83465 (12cm total dengan lubang)
    public const CONTINUOUS_HEIGHT_PT = 396.85;  // 140mm * 2.83465 (14cm per lembar)

    /**
     * Dimensi lebar thermal roll (72mm)
     */
    public const THERMAL_ROLL_WIDTH_PT = 204.1;  // 72mm * 2.83465

    /**
     * Entry point utama cetak thermal / continuous.
     * Otomatis membaca parameter ?layout=compact|detail|roll dari URL.
     */
    public static function notaInline(Sale $sale): \Illuminate\Http\Response
    {
        $layout = request()->query('layout', 'compact');

        return match ($layout) {
            'roll', 'thermal' => self::thermalRollInline($sale),
            'detail', '2row' => self::continuousDetailInline($sale),
            default => self::continuousCompactInline($sale),
        };
    }

    public static function continuousCompactInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator', 'receivable']);

        $html = view('reports.sale-thermal', [
            'sale' => $sale,
            'printedAt' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, self::CONTINUOUS_WIDTH_PT, self::CONTINUOUS_HEIGHT_PT], 'portrait');
        return $pdf->stream('Nota-Ringkas-'.$sale->transaction_number.'.pdf');
    }

    /**
     * Layout 2: Continuous Form 2-Baris Detail (9.5cm x 14cm)
     * Format nama barang di baris pertama dan rincian harga di baris kedua.
     */
    public static function continuousDetailInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator', 'receivable']);

        $html = view('reports.sale-continuous-detail', [
            'sale' => $sale,
            'printedAt' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, self::CONTINUOUS_WIDTH_PT, self::CONTINUOUS_HEIGHT_PT], 'portrait');
        return $pdf->stream('Nota-Detail-'.$sale->transaction_number.'.pdf');
    }

    /**
     * Layout 1: Struk Thermal Roll 72mm (Panjang Otomatis)
     * Format gulungan kasir POS standar (panjang kertas menyesuaikan jumlah item).
     */
    public static function thermalRollInline(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load(['items', 'party', 'creator', 'receivable']);

        $html = view('reports.sale-thermal-roll', [
            'sale' => $sale,
            'printedAt' => now()->format('d M Y H:i'),
            'printedBy' => auth()->user()?->name ?? '-',
        ])->render();

        $itemCount = $sale->items->count();
        $heightMm = max(120, 100 + ($itemCount * 10));
        $heightPt = $heightMm * 2.83465;

        $pdf = Pdf::loadHTML($html)->setPaper([0, 0, self::THERMAL_ROLL_WIDTH_PT, $heightPt], 'portrait');
        return $pdf->stream('Struk-Thermal-'.$sale->transaction_number.'.pdf');
    }

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
            ->setPaper([0, 0, self::CONTINUOUS_WIDTH_PT, self::CONTINUOUS_HEIGHT_PT], 'portrait')
            ->output());

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }
}
