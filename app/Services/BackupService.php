<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ReceivableParty;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupService
{
    /**
     * Mendapatkan path file database SQLite utama.
     */
    public function getDatabasePath(): string
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! file_exists($dbPath)) {
            $dbPath = database_path('database.sqlite');
        }

        return $dbPath;
    }

    /**
     * Mendapatkan informasi statistik database.
     *
     * @return array{file_size: string, last_modified: string, total_sales: int, total_products: int, total_customers: int, total_parties: int}
     */
    public function getDatabaseInfo(): array
    {
        $dbPath = $this->getDatabasePath();

        $size = file_exists($dbPath) ? filesize($dbPath) : 0;
        $modified = file_exists($dbPath) ? filemtime($dbPath) : time();

        return [
            'file_size' => $this->formatBytes($size),
            'last_modified' => Carbon::createFromTimestamp($modified)->format('d M Y H:i:s'),
            'total_sales' => Sale::count(),
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'total_parties' => ReceivableParty::count(),
        ];
    }

    /**
     * Membuat file backup dan men-download-nya secara langsung.
     */
    public function downloadBackup(): BinaryFileResponse
    {
        $dbPath = $this->getDatabasePath();

        if (! file_exists($dbPath)) {
            abort(404, 'File database tidak ditemukan.');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup-toko-kain-{$timestamp}.sqlite";

        return response()->download($dbPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }

    /**
     * Membuat salinan file backup ke dalam folder storage/app/backups.
     */
    public function createStoredBackup(): string
    {
        $dbPath = $this->getDatabasePath();

        if (! file_exists($dbPath)) {
            throw new \RuntimeException('File database tidak ditemukan.');
        }

        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFile = "{$backupDir}/backup-{$timestamp}.sqlite";

        File::copy($dbPath, $backupFile);

        return $backupFile;
    }

    /**
     * Mengambil daftar file backup yang pernah tersimpan di storage/app/backups.
     *
     * @return array<int, array{filename: string, filepath: string, size: string, created_at: string}>
     */
    public function listStoredBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            return [];
        }

        $files = File::files($backupDir);
        $result = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'sqlite') {
                $result[] = [
                    'filename' => $file->getFilename(),
                    'filepath' => $file->getPathname(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i:s'),
                    'mtime' => $file->getMTime(),
                ];
            }
        }

        usort($result, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return $result;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
