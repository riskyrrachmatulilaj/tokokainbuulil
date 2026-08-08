<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Backup Database';

    protected static ?string $title = 'Backup & Cadangan Database';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.backup-page';

    public array $info = [];

    public array $storedBackups = [];

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $service = app(BackupService::class);
        $this->info = $service->getDatabaseInfo();
        $this->storedBackups = $service->listStoredBackups();
    }

    public function downloadLiveBackup(): BinaryFileResponse
    {
        Notification::make()
            ->success()
            ->title('Mulai Mendownload Backup')
            ->body('File database SQLite sedang didownload ke perangkat Anda.')
            ->send();

        return app(BackupService::class)->downloadBackup();
    }

    public function saveStoredBackup(): void
    {
        try {
            $path = app(BackupService::class)->createStoredBackup();
            $this->refreshData();

            Notification::make()
                ->success()
                ->title('Backup Berhasil Disimpan')
                ->body('Salinan database berhasil disimpan ke sistem.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Membuat Backup')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function downloadStoredFile(string $filepath): BinaryFileResponse
    {
        if (! file_exists($filepath)) {
            Notification::make()
                ->danger()
                ->title('File Tidak Ditemukan')
                ->send();

            abort(404);
        }

        return response()->download($filepath);
    }

    public function deleteStoredFile(string $filepath): void
    {
        if (file_exists($filepath)) {
            unlink($filepath);
            $this->refreshData();

            Notification::make()
                ->success()
                ->title('File Backup Dihapus')
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_direct')
                ->label('Download Database Sekarang')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->downloadLiveBackup()),

            Action::make('create_snapshot')
                ->label('Simpan Cadangan Lokal')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->action(fn () => $this->saveStoredBackup()),
        ];
    }
}
