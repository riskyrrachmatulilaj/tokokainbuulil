<?php

namespace App\Exports;

use App\Services\TopCustomerReportService;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopCustomerReportExport
{
    protected static array $customerColumns = [
        'Peringkat',
        'Nama Pelanggan',
        'No. Telepon / WA',
        'Alamat',
        'Jumlah Transaksi (Nota)',
        'Total Belanja (Rp)',
        'Total Kuantitas',
        'Rata-rata / Transaksi (Rp)',
        'Transaksi Terakhir',
        'Sisa Piutang Aktif (Rp)',
    ];

    public static function xlsx(array $filters = []): BinaryFileResponse
    {
        $report = app(TopCustomerReportService::class)->data($filters);

        $filename = 'Laporan-Pelanggan-Terbanyak-' . Carbon::now()->format('Ymd-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'topcust_') . '.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(13, 148, 136));

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $writer->addRow(Row::fromValues(['LAPORAN PELANGGAN DENGAN TRANSAKSI TERBANYAK'], (new Style())->setFontBold()->setFontSize(14)));
        $writer->addRow(Row::fromValues(['Periode: ' . $report['filters']['period_label']]));
        $writer->addRow(Row::fromValues(['Urutan Berdasarkan: ' . $report['filters']['sort_label']]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['RINGKASAN'], (new Style())->setFontBold()));
        $writer->addRow(Row::fromValues([
            'Pelanggan Aktif: ' . $report['summary']['active_customers_count'],
            'Transaksi Pelanggan: ' . $report['summary']['customer_transactions'],
            'Omset Pelanggan: Rp ' . number_format($report['summary']['customer_revenue'], 0, ',', '.'),
        ]));
        $writer->addRow(Row::fromValues([
            'Transaksi Umum (Walk-in): ' . $report['summary']['walk_in_transactions'],
            'Omset Pelanggan Umum: Rp ' . number_format($report['summary']['walk_in_revenue'], 0, ',', '.'),
            'Grand Total Toko: Rp ' . number_format($report['summary']['grand_total_revenue'], 0, ',', '.'),
        ]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(array_map(fn (string $col) => mb_strtoupper($col), self::$customerColumns), $headerStyle));

        foreach ($report['customers'] as $cust) {
            $writer->addRow(Row::fromValues([
                '#' . $cust['rank'],
                $cust['name'],
                $cust['phone'] ?? '-',
                $cust['address'] ?? '-',
                $cust['transaction_count'],
                $cust['total_spent'],
                $cust['total_quantity'],
                $cust['average_spent'],
                $cust['last_sale_date_formatted'],
                $cust['unpaid_receivable'],
            ]));
        }

        $writer->close();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
