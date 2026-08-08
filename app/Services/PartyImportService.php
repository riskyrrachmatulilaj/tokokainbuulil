<?php

namespace App\Services;

use App\Support\PartyImportResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class PartyImportService
{
    /** @var array<string, string> header alias => canonical field */
    protected array $headerMap = [
        'nama' => 'name',
        'name' => 'name',
        'nama pelanggan' => 'name',
        'nama debitur' => 'name',
        'telepon' => 'phone',
        'telp' => 'phone',
        'no telepon' => 'phone',
        'no. telepon' => 'phone',
        'nomor telepon' => 'phone',
        'phone' => 'phone',
        'hp' => 'phone',
        'no hp' => 'phone',
        'alamat' => 'address',
        'address' => 'address',
    ];

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function import(string $filePath, string $modelClass, ?string $originalExtension = null): PartyImportResult
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'file' => 'Model import tidak valid.',
            ]);
        }

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

        $result = new PartyImportResult();
        $seenInFile = [];

        DB::transaction(function () use ($rows, $columnIndexes, $modelClass, &$result, &$seenInFile) {
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

                $matchKey = $this->matchKey($payload['name'], $payload['phone']);

                if (isset($seenInFile[$matchKey])) {
                    $result->failures[] = [
                        'row' => $rowNumber,
                        'reason' => 'Duplikat dalam berkas (sama dengan baris '.$seenInFile[$matchKey].').',
                    ];
                    $result->failuresCount++;

                    continue;
                }

                $seenInFile[$matchKey] = $rowNumber;

                $existing = $this->findExisting($modelClass, $payload['name'], $payload['phone']);

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->fill([
                        'name' => $payload['name'],
                        'phone' => $payload['phone'],
                        'address' => $payload['address'],
                    ]);
                    $existing->save();
                    $result->updated++;

                    continue;
                }

                $modelClass::query()->create([
                    'name' => $payload['name'],
                    'phone' => $payload['phone'],
                    'address' => $payload['address'],
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
     * @return array{name: string, phone: ?string, address: ?string}
     */
    protected function extractPayload(array $rawValues, array $columnIndexes): array
    {
        $name = $this->cellString($rawValues[$columnIndexes['name']] ?? null);
        $phone = array_key_exists('phone', $columnIndexes)
            ? $this->normalizePhone($this->cellString($rawValues[$columnIndexes['phone']] ?? null))
            : null;
        $address = array_key_exists('address', $columnIndexes)
            ? $this->cellString($rawValues[$columnIndexes['address']] ?? null)
            : null;

        return [
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
        ];
    }

    /**
     * @param  array{name: string, phone: ?string, address: ?string}  $payload
     * @return list<string>
     */
    protected function validatePayload(array $payload): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors[] = 'Nama wajib diisi';
        } elseif (mb_strlen($payload['name']) > 255) {
            $errors[] = 'Nama maksimal 255 karakter';
        }

        if ($payload['phone'] !== null && mb_strlen($payload['phone']) > 30) {
            $errors[] = 'Telepon maksimal 30 karakter';
        }

        if ($payload['address'] !== null && mb_strlen($payload['address']) > 1000) {
            $errors[] = 'Alamat maksimal 1000 karakter';
        }

        return $errors;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function findExisting(string $modelClass, string $name, ?string $phone): ?Model
    {
        $query = $modelClass::query()
            ->withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($phone === null || $phone === '') {
            $query->where(function ($q) {
                $q->whereNull('phone')->orWhere('phone', '');
            });
        } else {
            $query->where('phone', $phone);
        }

        return $query->first();
    }

    protected function matchKey(string $name, ?string $phone): string
    {
        return mb_strtolower($name).'|'.($phone ?? '');
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
            // Excel may store telepon as angka; hindari notasi ilmiah.
            return rtrim(rtrim(sprintf('%.12F', $value), '0'), '.') ?: '0';
        }

        return trim((string) $value);
    }

    protected function normalizePhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        // Keep leading +, strip spaces/dashes/dots commonly typed in Excel
        $phone = preg_replace('/[\s\-\.\(\)]+/', '', $phone) ?? $phone;

        return trim($phone);
    }

    /**
     * Helper for tests / tooling — create reader from path via factory when extension known.
     */
    public function createReaderFromPath(string $path): ReaderInterface
    {
        return ReaderFactory::createFromFile($path);
    }
}
