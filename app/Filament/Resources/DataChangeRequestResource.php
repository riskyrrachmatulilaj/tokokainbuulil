<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataChangeRequestResource\Pages;
use App\Models\DataChangeRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DataChangeRequestResource extends Resource
{
    protected static ?string $model = DataChangeRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Penjualan';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isAdmin() ? 'Persetujuan Perubahan' : 'Pengajuan Perubahan';
    }

    public static function getModelLabel(): string
    {
        return 'Permohonan Perubahan Data';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Permohonan Perubahan Data';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $query = DataChangeRequest::where('status', DataChangeRequest::STATUS_PENDING);
        if ($user->isKasir()) {
            $query->where('user_id', $user->id);
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'reviewer', 'requestable']);

        if (auth()->user()?->isKasir()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir Pengaju')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_data.transaction_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->copyable()
                    ->placeholder(fn (DataChangeRequest $record) => "#{$record->requestable_id}")
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->badge()
                    ->color(fn (DataChangeRequest $record) => match ($record->request_type) {
                        DataChangeRequest::TYPE_EDIT => 'info',
                        DataChangeRequest::TYPE_VOID => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (DataChangeRequest $record) => $record->request_type_label),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan Kasir')
                    ->limit(40)
                    ->tooltip(fn (DataChangeRequest $record) => $record->reason)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DataChangeRequest $record) => $record->status_badge_color)
                    ->formatStateUsing(fn (DataChangeRequest $record) => $record->status_label)
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Waktu Review')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        DataChangeRequest::STATUS_PENDING => 'Menunggu Persetujuan',
                        DataChangeRequest::STATUS_APPROVED => 'Disetujui',
                        DataChangeRequest::STATUS_REJECTED => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('request_type')
                    ->label('Jenis Pengajuan')
                    ->options([
                        DataChangeRequest::TYPE_EDIT => 'Koreksi Data',
                        DataChangeRequest::TYPE_VOID => 'Pembatalan Transaksi',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DataChangeRequest $record) => auth()->user()?->isAdmin() && $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Perubahan Data')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pengajuan ini? Data penjualan, stok kain, dan piutang terkait akan disesuaikan otomatis oleh sistem.')
                    ->action(function (DataChangeRequest $record) {
                        try {
                            app(\App\Services\DataChangeRequestService::class)->approveRequest($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Pengajuan Disetujui')
                                ->body('Perubahan data berhasil diaplikasikan ke sistem.')
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menyetujui')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DataChangeRequest $record) => auth()->user()?->isAdmin() && $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->placeholder('Tuliskan alasan mengapa pengajuan ini ditolak...')
                            ->required()
                            ->minLength(5)
                            ->rows(3),
                    ])
                    ->modalHeading('Tolak Pengajuan Perubahan Data')
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->action(function (DataChangeRequest $record, array $data) {
                        try {
                            app(\App\Services\DataChangeRequestService::class)->rejectRequest(
                                $record,
                                auth()->user(),
                                $data['rejection_reason']
                            );

                            Notification::make()
                                ->success()
                                ->title('Pengajuan Ditolak')
                                ->body('Kasir akan menerima notifikasi alasan penolakan.')
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menolak')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataChangeRequests::route('/'),
            'view' => Pages\ViewDataChangeRequest::route('/{record}'),
        ];
    }
}
