<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportTemplateExport
{
    /**
     * @return list<string>
     */
    public static function columns(): array
    {
        return ['Nama', 'Harga', 'Keterangan', 'Status'];
    }

    /**
     * @return list<list<string>>
     */
    public static function exampleRows(): array
    {
        return [
            ['Kain Batik Pekalongan (2 m)', '150000', 'Bahan katun halus motif parang', 'Aktif'],
            ['Kain Sutra Satin', '250000', 'Sutra asli premium lebar 1.5 m', 'Aktif'],
            ['Kain Brokat Kebaya', '85000', 'Warna rose gold', 'Aktif'],
            ['Kain Katun Jepang (Habis)', '45000', 'Nonaktif sementara', 'Nonaktif'],
        ];
    }

    public static function filename(): string
    {
        return 'template-import-produk.xlsx';
    }

    public static function download(): BinaryFileResponse
    {
        $filename = self::filename();
        $tempFile = tempnam(sys_get_temp_dir(), 'prod_import_tpl_').'.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(79, 70, 229));

        $writer = new Writer();
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues(self::columns(), $headerStyle));

        foreach (self::exampleRows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return response()->download(
            $tempFile,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
