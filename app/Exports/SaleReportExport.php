<?php

namespace App\Exports;

use App\Services\SaleReportService;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReportExport
{
    protected static array $saleColumns = ['No. Transaksi', 'Jam', 'Metode Pembayaran', 'Pelanggan', 'Jumlah Item', 'Total'];

    protected static array $itemColumns = ['Produk', 'Jumlah Terjual', 'Pendapatan'];

    public static function xlsx(string $date): BinaryFileResponse
    {
        $report = app(SaleReportService::class)->data($date);

        $filename = 'Laporan-Penjualan-Harian-'.Carbon::parse($date)->format('Ymd').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sreport_').'.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(13, 148, 136));

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $writer->addRow(Row::fromValues(['LAPORAN PENJUALAN HARIAN - '.$report['date']], (new Style())->setFontBold()->setFontSize(14)));
        $writer->addRow(Row::fromValues([
            'Transaksi: '.$report['summary']['transactions'],
            'Total: '.number_format($report['summary']['total_revenue'], 0, ',', '.'),
        ]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(array_map(fn (string $column) => mb_strtoupper($column), self::$saleColumns), $headerStyle));

        foreach ($report['sales'] as $row) {
            $writer->addRow(Row::fromValues([
                $row['transaction_number'],
                $row['time'],
                $row['payment_method_label'],
                $row['party'] ?? '-',
                $row['items_count'],
                $row['total_amount'],
            ]));
        }

        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(array_map(fn (string $column) => mb_strtoupper($column), self::$itemColumns), $headerStyle));

        foreach ($report['items'] as $item) {
            $writer->addRow(Row::fromValues([
                $item['product_name'],
                $item['quantity'],
                $item['revenue'],
            ]));
        }

        $writer->close();

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }
}
