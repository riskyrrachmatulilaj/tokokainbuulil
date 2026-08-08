<?php

namespace App\Services;

use App\Models\Product;
use App\Support\ProductImportResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\ReaderInterface;

class ProductImportService
{
    /** @var array<string, string> header alias => canonical field */
    protected array $headerMap = [
        'nama' => 'name',
        'name' => 'name',
        'nama produk' => 'name',
        'nama barang' => 'name',
        'harga' => 'price',
        'price' => 'price',
        'harga jual' => 'price',
        'keterangan' => 'description',
        'description' => 'description',
        'deskripsi' => 'description',
        'status' => 'status',
        'aktif' => 'status',
        'is_active' => 'status',
    ];

    public function import(string $filePath, ?string $originalExtension = null): ProductImportResult
    {
        $extension = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Format berkas tidak didukung. Gunakan .xlsx atau .csv.',
            ]);
        }

        if (! is_readable($filePath)) {
            throw ValidationException::withMessages([
                'file' => 'Berkas tidak dapat dibaca.',
            ]);
        }

        $reader = $this->makeReader($extension);
        $reader->open($filePath);

        try {
            $rows = $this->readRows($reader);
        } finally {
            $reader->close();
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Berkas kosong atau tidak memiliki data.',
            ]);
        }

        $headerRow = array_shift($rows);
        $columnIndexes = $this->resolveColumnIndexes($headerRow);

        if (! array_key_exists('name', $columnIndexes)) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib "Nama" tidak ditemukan. Unduh template untuk format yang benar.',
            ]);
        }

        if (! array_key_exists('price', $columnIndexes)) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib "Harga" tidak ditemukan. Unduh template untuk format yang benar.',
            ]);
        }

        $result = new ProductImportResult();
        $seenInFile = [];

        DB::transaction(function () use ($rows, $columnIndexes, &$result, &$seenInFile) {
            foreach ($rows as $offset => $rawValues) {
                // Excel row number: header is row 1, first data row is 2
                $rowNumber = $offset + 2;

                if ($this->rowIsEmpty($rawValues)) {
                    continue;
                }

                $payload = $this->extractPayload($rawValues, $columnIndexes);
                $errors = $this->validatePayload($payload);

                if ($errors !== []) {
                    $result->failures[] = [
                        'row' => $rowNumber,
                        'reason' => implode('; ', $errors),
                    ];
                    $result->failuresCount++;

                    continue;
                }

                $matchKey = mb_strtolower($payload['name']);

                if (isset($seenInFile[$matchKey])) {
                    $result->failures[] = [
                        'row' => $rowNumber,
                        'reason' => 'Duplikat dalam berkas (sama dengan baris '.$seenInFile[$matchKey].').',
                    ];
                    $result->failuresCount++;

                    continue;
                }

                $seenInFile[$matchKey] = $rowNumber;

                $existing = Product::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($payload['name'])])
                    ->first();

                if ($existing) {
                    $existing->fill([
                        'price' => $payload['price'],
                        'description' => $payload['description'],
                        'is_active' => $payload['is_active'],
                    ]);
                    $existing->save();
                    $result->updated++;

                    continue;
                }

                Product::query()->create([
                    'name' => $payload['name'],
                    'price' => $payload['price'],
                    'description' => $payload['description'],
                    'is_active' => $payload['is_active'],
                ]);
                $result->created++;
            }
        });

        return $result;
    }

    protected function makeReader(string $extension): ReaderInterface
    {
        return match ($extension) {
            'csv' => new CsvReader(),
            default => new XlsxReader(),
        };
    }

    /**
     * @return list<list<mixed>>
     */
    protected function readRows(ReaderInterface $reader): array
    {
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            // Only first sheet
            break;
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int>
     */
    protected function resolveColumnIndexes(array $headerRow): array
    {
        $indexes = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);

            if ($normalized === '' || ! isset($this->headerMap[$normalized])) {
                continue;
            }

            $field = $this->headerMap[$normalized];

            if (! isset($indexes[$field])) {
                $indexes[$field] = (int) $index;
            }
        }

        return $indexes;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = Str::of($header)
            ->replace("\u{FEFF}", '')
            ->lower()
            ->trim()
            ->toString();

        return preg_replace('/\s+/', ' ', $header) ?? $header;
    }

    /**
     * @param  list<mixed>  $rawValues
     * @param  array<string, int>  $columnIndexes
     * @return array{name: string, price: float, description: ?string, is_active: bool}
     */
    protected function extractPayload(array $rawValues, array $columnIndexes): array
    {
        $name = $this->cellString($rawValues[$columnIndexes['name']] ?? null);
        
        $priceVal = $rawValues[$columnIndexes['price']] ?? null;
        $price = is_numeric($priceVal) ? (float) $priceVal : 0.0;

        $description = array_key_exists('description', $columnIndexes)
            ? $this->cellString($rawValues[$columnIndexes['description']] ?? null)
            : null;

        $statusStr = array_key_exists('status', $columnIndexes)
            ? mb_strtolower($this->cellString($rawValues[$columnIndexes['status']] ?? null))
            : 'aktif';

        $isActive = ! in_array($statusStr, ['nonaktif', '0', 'false', 'non-aktif', 'no', 'tidak aktif'], true);

        return [
            'name' => $name,
            'price' => $price,
            'description' => $description !== '' ? $description : null,
            'is_active' => $isActive,
        ];
    }

    /**
     * @param  array{name: string, price: float, description: ?string, is_active: bool}  $payload
     * @return list<string>
     */
    protected function validatePayload(array $payload): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors[] = 'Nama Produk wajib diisi';
        } elseif (mb_strlen($payload['name']) > 255) {
            $errors[] = 'Nama Produk maksimal 255 karakter';
        }

        if ($payload['price'] <= 0) {
            $errors[] = 'Harga Jual wajib berupa angka lebih dari 0';
        }

        if ($payload['description'] !== null && mb_strlen($payload['description']) > 1000) {
            $errors[] = 'Keterangan maksimal 1000 karakter';
        }

        return $errors;
    }

    /**
     * @param  list<mixed>  $rawValues
     */
    protected function rowIsEmpty(array $rawValues): bool
    {
        foreach ($rawValues as $value) {
            if (trim($this->cellString($value)) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function cellString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.12F', $value), '0'), '.') ?: '0';
        }

        return trim((string) $value);
    }
}
