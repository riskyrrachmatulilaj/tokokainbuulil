<?php

namespace App\Filament\Actions;

use App\Exports\ProductImportTemplateExport;
use App\Services\ProductImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportProductsAction
{
    public static function make(): Action
    {
        return Action::make('import_products')
            ->label('Impor Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->visible(fn (): bool => Gate::allows('import', \App\Models\Product::class))
            ->modalHeading("Impor Produk")
            ->modalDescription("Unggah berkas Excel (.xlsx) atau CSV untuk menambah/memperbarui produk secara batch.")
            ->modalSubmitActionLabel('Mulai Impor')
            ->modalWidth('xl')
            ->schema([
                Section::make('Petunjuk Import')
                    ->description('Baca sebelum mengunggah berkas.')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Html::make(new HtmlString(
                            view('filament.import.product-import-instructions')->render()
                        )),
                        SchemaActions::make([
                            Action::make('downloadTemplate')
                                ->label('Unduh Template Excel')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('gray')
                                ->action(fn () => ProductImportTemplateExport::download()),
                        ]),
                    ]),
                FileUpload::make('file')
                    ->label('Berkas Excel / CSV')
                    ->helperText('Format: .xlsx atau .csv. Gunakan template di atas agar kolom sesuai.')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                        'application/csv',
                    ])
                    ->rules(['required', 'file', 'mimes:xlsx,csv,txt'])
                    ->disk('local')
                    ->directory('imports/tmp')
                    ->visibility('private')
                    ->required()
                    ->maxSize(5120),
            ])
            ->action(function (array $data): void {
                $uploaded = $data['file'] ?? null;

                if (is_array($uploaded)) {
                    $uploaded = $uploaded[0] ?? null;
                }

                $resolved = self::resolveUploadedPath($uploaded);

                if ($resolved === null) {
                    Notification::make()
                        ->danger()
                        ->title('Impor gagal')
                        ->body('Berkas tidak ditemukan. Unggah ulang berkas Excel/CSV.')
                        ->send();

                    return;
                }

                [$absolutePath, $extension] = $resolved;

                try {
                    $result = app(ProductImportService::class)->import(
                        $absolutePath,
                        $extension,
                    );
                } catch (ValidationException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Impor gagal')
                        ->body(collect($e->errors())->flatten()->first() ?: 'Validasi berkas gagal.')
                        ->send();

                    return;
                } finally {
                    self::cleanupUploaded($uploaded, $absolutePath ?? null);
                }

                $title = $result->hasFailures()
                    ? "Impor produk selesai dengan peringatan"
                    : "Impor produk berhasil";

                $notification = Notification::make()
                    ->title($title)
                    ->body($result->summaryBody())
                    ->seconds(12);

                if ($result->imported() > 0 && ! $result->hasFailures()) {
                    $notification->success();
                } elseif ($result->imported() > 0) {
                    $notification->warning();
                } else {
                    $notification->danger();
                }

                $notification->send();
            });
    }

    /**
     * @return array{0: string, 1: string}|null [absolutePath, extension]
     */
    protected static function resolveUploadedPath(mixed $uploaded): ?array
    {
        if ($uploaded instanceof TemporaryUploadedFile) {
            $path = $uploaded->getRealPath() ?: $uploaded->getPathname();
            $extension = strtolower($uploaded->getClientOriginalExtension() ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_EXTENSION));

            return is_string($path) && is_readable($path)
                ? [$path, $extension ?: 'xlsx']
                : null;
        }

        if (is_string($uploaded) && $uploaded !== '') {
            $absolute = storage_path('app/private/'.$uploaded);
            if (! is_readable($absolute)) {
                $absolute = storage_path('app/'.$uploaded);
            }

            if (! is_readable($absolute)) {
                return null;
            }

            return [$absolute, strtolower(pathinfo($absolute, PATHINFO_EXTENSION) ?: 'xlsx')];
        }

        return null;
    }

    protected static function cleanupUploaded(mixed $uploaded, ?string $absolutePath): void
    {
        if ($uploaded instanceof TemporaryUploadedFile) {
            try {
                $uploaded->delete();
            } catch (\Throwable) {
                // ignore
            }

            return;
        }

        if (is_string($uploaded) && $uploaded !== '') {
            try {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($uploaded);
            } catch (\Throwable) {
                // ignore
            }
        }
    }
}
