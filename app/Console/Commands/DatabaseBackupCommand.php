<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Membuat backup salinan database SQLite ke folder storage/app/backups';

    public function handle(BackupService $backupService): int
    {
        $this->info('Memulai proses backup database...');

        try {
            $file = $backupService->createStoredBackup();
            $this->info('Backup berhasil dibuat: '.$file);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal membuat backup: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
