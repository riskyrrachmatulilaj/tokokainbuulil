<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportToMysqlCommand extends Command
{
    protected $signature = 'db:export-mysql {--output=database/export_mysql.sql : Path file output SQL}';

    protected $description = 'Export data dari SQLite ke file SQL yang kompatibel dengan MySQL (untuk import ke cPanel phpMyAdmin)';

    /**
     * Tabel-tabel data aplikasi yang perlu diekspor.
     * Tabel sistem (sessions, cache, jobs, migrations, dll) tidak perlu diekspor
     * karena akan dibuat ulang oleh artisan migrate.
     */
    private array $dataTables = [
        'users',
        'customers',
        'debts',
        'installments',
        'collective_payments',
        'payment_histories',
        'receivable_parties',
        'receivables',
        'receivable_installments',
        'receivable_collective_payments',
        'receivable_payment_histories',
        'products',
        'sales',
        'sale_items',
    ];

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Perintah ini hanya bisa dijalankan saat DB_CONNECTION=sqlite.');
            $this->error('Pastikan .env menggunakan DB_CONNECTION=sqlite');

            return self::FAILURE;
        }

        $outputPath = $this->option('output');
        $fullPath = base_path($outputPath);

        $this->info('===========================================');
        $this->info('  Export SQLite → MySQL SQL');
        $this->info('===========================================');
        $this->newLine();

        $sql = $this->generateHeader();

        $totalRows = 0;
        $bar = $this->output->createProgressBar(count($this->dataTables));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');

        foreach ($this->dataTables as $table) {
            $bar->setMessage("Mengekspor tabel: {$table}");
            $bar->advance();

            if (! Schema::hasTable($table)) {
                $this->newLine();
                $this->warn("  ⚠ Tabel '{$table}' tidak ditemukan, dilewati.");

                continue;
            }

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                $sql .= "-- Tabel `{$table}`: kosong (0 baris)\n\n";

                continue;
            }

            $totalRows += $rows->count();
            $sql .= $this->generateInsertStatements($table, $rows);
        }

        $bar->setMessage('Selesai!');
        $bar->finish();
        $this->newLine(2);

        $sql .= $this->generateFooter();

        // Tulis file
        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $sql);

        $fileSize = $this->formatBytes(filesize($fullPath));

        $this->info("✅ Export berhasil!");
        $this->newLine();
        $this->table(
            ['Info', 'Nilai'],
            [
                ['File', $fullPath],
                ['Ukuran', $fileSize],
                ['Total baris', number_format($totalRows)],
                ['Jumlah tabel', count($this->dataTables)],
            ]
        );

        $this->newLine();
        $this->info('📋 Langkah selanjutnya:');
        $this->line('  1. Upload project ke cPanel (tanpa folder database/database.sqlite)');
        $this->line('  2. Rename .env.mysql menjadi .env (sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD)');
        $this->line('  3. Jalankan: php artisan migrate');
        $this->line('  4. Import file SQL ini via phpMyAdmin di cPanel');
        $this->line('  5. Jalankan: php artisan optimize');
        $this->newLine();

        return self::SUCCESS;
    }

    private function generateHeader(): string
    {
        $date = now()->format('Y-m-d H:i:s');

        return <<<SQL
-- ============================================================
-- Export Data: Toko Kain Bu Ulil (SQLite → MySQL)
-- Tanggal: {$date}
-- ============================================================
-- PETUNJUK:
-- 1. Pastikan sudah menjalankan `php artisan migrate` di server
--    untuk membuat struktur tabel MySQL terlebih dahulu.
-- 2. Import file ini via phpMyAdmin di cPanel.
-- 3. File ini HANYA berisi data (INSERT), BUKAN struktur tabel.
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;


SQL;
    }

    private function generateFooter(): string
    {
        return <<<SQL

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- Export selesai. Semoga sukses deploy di cPanel! 🎉
-- ============================================================
SQL;
    }

    /**
     * Ambil daftar kolom yang bertipe numerik dari schema SQLite.
     */
    private function getNumericColumns(string $table): array
    {
        $columns = DB::select("PRAGMA table_info(`{$table}`)");
        $numericColumns = [];

        foreach ($columns as $col) {
            $type = strtolower($col->type);
            if (
                str_contains($type, 'int') ||
                str_contains($type, 'real') ||
                str_contains($type, 'float') ||
                str_contains($type, 'double') ||
                str_contains($type, 'decimal') ||
                str_contains($type, 'numeric')
            ) {
                $numericColumns[] = $col->name;
            }
        }

        return $numericColumns;
    }

    private function generateInsertStatements(string $table, \Illuminate\Support\Collection $rows): string
    {
        $sql = "-- ----------------------------------------------------------\n";
        $sql .= "-- Tabel `{$table}`: {$rows->count()} baris\n";
        $sql .= "-- ----------------------------------------------------------\n";

        // Hapus data lama (jika ada) agar tidak duplikat saat re-import
        $sql .= "DELETE FROM `{$table}`;\n";

        // Ambil kolom numerik dari schema
        $numericColumns = $this->getNumericColumns($table);

        // Batch insert per 50 baris untuk efisiensi
        $chunks = $rows->chunk(50);

        foreach ($chunks as $chunk) {
            $columns = array_keys((array) $chunk->first());
            $columnList = implode('`, `', $columns);

            $sql .= "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n";

            $values = [];
            foreach ($chunk as $row) {
                $rowValues = [];
                foreach ((array) $row as $column => $value) {
                    $isNumeric = in_array($column, $numericColumns);
                    $rowValues[] = $this->escapeValue($value, $isNumeric);
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }

            $sql .= implode(",\n", $values) . ";\n\n";
        }

        return $sql;
    }

    private function escapeValue(mixed $value, bool $isNumericColumn = false): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Hanya tampilkan tanpa quote jika kolom memang bertipe numerik
        if ($isNumericColumn && is_numeric($value)) {
            return (string) $value;
        }

        // Semua nilai lainnya di-quote sebagai string
        $value = str_replace(
            ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            (string) $value
        );

        return "'{$value}'";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
