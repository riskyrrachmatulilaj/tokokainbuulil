<?php

namespace App\Exports;

use App\Services\ReceivableReportService;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceivableReportExport
{
    protected static array $columns = [
        ReceivableReportService::TYPE_RECEIVABLE_LIST => ['No. Nota', 'Debitur', 'Tanggal Piutang', 'Nominal', 'Sudah Diterima', 'Sisa Piutang', 'Status'],
        ReceivableReportService::TYPE_PAYMENT_HISTORY => ['No. Transaksi', 'Debitur', 'No. Nota', 'Jenis Pembayaran', 'Nominal', 'Tanggal', 'Oleh'],
        ReceivableReportService::TYPE_PAYMENT_PERIOD => ['Periode', 'Jumlah Transaksi', 'Total'],
        ReceivableReportService::TYPE_UNPAID_RECEIVABLES => ['No. Nota', 'Debitur', 'Tanggal Piutang', 'Jatuh Tempo', 'Nominal', 'Sudah Diterima', 'Sisa Piutang'],
        ReceivableReportService::TYPE_OVERDUE_PARTIES => ['Debitur', 'Telepon', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo', 'Total Sisa Piutang', 'Piutang Tertua'],
    ];

    public static function xlsx(string $type, array $filters): BinaryFileResponse
    {
        $service = app(ReceivableReportService::class);
        $rows = $service->data($filters);
        $title = $service->title($type);

        $filename = 'Laporan-'.Str::slug($title).'-'.now()->format('Ymd-His').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'rreport_').'.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(5, 150, 105));

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $writer->addRow(Row::fromValues(
            array_map(fn (string $column) => mb_strtoupper($column), self::columnsFor($type)),
            $headerStyle
        ));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(self::valuesFor($type, $row)));
        }

        $writer->close();

        return response()->download($tempFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public static function columnsFor(string $type): array
    {
        return self::$columns[$type] ?? self::$columns[ReceivableReportService::TYPE_RECEIVABLE_LIST];
    }

    public static function valuesFor(string $type, array $row): array
    {
        $order = match ($type) {
            ReceivableReportService::TYPE_PAYMENT_HISTORY => ['transaction_number', 'party', 'invoice_number', 'payment_type', 'amount', 'payment_date', 'creator'],
            ReceivableReportService::TYPE_PAYMENT_PERIOD => ['period', 'count', 'total'],
            ReceivableReportService::TYPE_UNPAID_RECEIVABLES => ['invoice_number', 'party', 'receivable_date', 'due_date', 'amount', 'paid_amount', 'remaining_amount'],
            ReceivableReportService::TYPE_OVERDUE_PARTIES => ['party', 'phone', 'unpaid_count', 'overdue_count', 'remaining_amount', 'oldest_receivable_date'],
            default => ['invoice_number', 'party', 'receivable_date', 'amount', 'paid_amount', 'remaining_amount', 'status'],
        };

        return array_map(fn (string $key) => $row[$key] ?? '', $order);
    }
}
