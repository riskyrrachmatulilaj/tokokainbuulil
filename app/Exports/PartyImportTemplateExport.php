<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartyImportTemplateExport
{
    public const TYPE_PELANGGAN = 'pelanggan';

    public const TYPE_DEBITUR = 'debitur';

    /**
     * @return list<string>
     */
    public static function columns(): array
    {
        return ['Nama', 'Telepon', 'Alamat'];
    }

    /**
     * @return list<list<string>>
     */
    public static function exampleRows(string $type): array
    {
        return match ($type) {
            self::TYPE_DEBITUR => [
                ['CV Maju Jaya', '081234567890', 'Jl. Industri No. 5, Bandung'],
                ['Toko Berkah', '081298765432', 'Jl. Pasar Baru No. 8, Cimahi'],
                ['Bapak Andi Wijaya', '', 'Jl. Melati No. 3, Garut'],
            ],
            default => [
                ['Budi Santoso', '081234567890', 'Jl. Melati No. 12, Jakarta'],
                ['Siti Aminah', '081298765432', 'Jl. Kenanga No. 5, Bandung'],
                ['Ahmad Fauzi', '', 'Jl. Merdeka No. 1, Surabaya'],
            ],
        };
    }

    public static function filename(string $type): string
    {
        return match ($type) {
            self::TYPE_DEBITUR => 'template-import-debitur.xlsx',
            default => 'template-import-pelanggan.xlsx',
        };
    }

    public static function download(string $type): BinaryFileResponse
    {
        $filename = self::filename($type);
        $tempFile = tempnam(sys_get_temp_dir(), 'party_import_tpl_').'.xlsx';

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::rgb(79, 70, 229));

        $writer = new Writer();
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues(self::columns(), $headerStyle));

        foreach (self::exampleRows($type) as $row) {
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
