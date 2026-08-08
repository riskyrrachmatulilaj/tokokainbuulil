<?php

namespace App\Exports;

use App\Services\ReportService;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExport
{
    protected static array $columns = [
        ReportService::TYPE_DEBT_LIST => ['No. Nota', 'Pelanggan', 'Tanggal Hutang', 'Nominal', 'Sudah Dibayar', 'Sisa Hutang', 'Status'],
        ReportService::TYPE_PAYMENT_HISTORY => ['No. Transaksi', 'Pelanggan', 'No. Nota', 'Jenis Pembayaran', 'Nominal', 'Tanggal', 'Oleh'],
        ReportService::TYPE_PAYMENT_PERIOD => ['Periode', 'Jumlah Transaksi', 'Total'],
        ReportService::TYPE_UNPAID_DEBTS => ['No. Nota', 'Pelanggan', 'Tanggal Hutang', 'Jatuh Tempo', 'Nominal', 'Sudah Dibayar', 'Sisa Hutang'],
        ReportService::TYPE_OVERDUE_CUSTOMERS => ['Pelanggan', 'Telepon', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo', 'Total Sisa Hutang', 'Hutang Tertua'],
    ];

    public static function xlsx(string $type, array $filters): BinaryFileResponse
    {
        $service = app(ReportService::class);
        $rows = $service->data($filters);
        $title = $service->title($type);

        $filename = 'Laporan-'.Str::slug($title).'-'.now()->format('Ymd-His').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'report_').'.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(79, 70, 229));

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
        return self::$columns[$type] ?? self::$columns[ReportService::TYPE_DEBT_LIST];
    }

    public static function valuesFor(string $type, array $row): array
    {
        $order = match ($type) {
            ReportService::TYPE_PAYMENT_HISTORY => ['transaction_number', 'customer', 'invoice_number', 'payment_type', 'amount', 'payment_date', 'creator'],
            ReportService::TYPE_PAYMENT_PERIOD => ['period', 'count', 'total'],
            ReportService::TYPE_UNPAID_DEBTS => ['invoice_number', 'customer', 'debt_date', 'due_date', 'amount', 'paid_amount', 'remaining_amount'],
            ReportService::TYPE_OVERDUE_CUSTOMERS => ['customer', 'phone', 'unpaid_count', 'overdue_count', 'remaining_amount', 'oldest_debt_date'],
            default => ['invoice_number', 'customer', 'debt_date', 'amount', 'paid_amount', 'remaining_amount', 'status'],
        };

        return array_map(fn (string $key) => $row[$key] ?? '', $order);
    }
}
