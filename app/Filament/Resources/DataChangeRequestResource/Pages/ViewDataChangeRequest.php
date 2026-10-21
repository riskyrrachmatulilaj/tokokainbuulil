<?php

namespace App\Filament\Resources\DataChangeRequestResource\Pages;

use App\Filament\Resources\DataChangeRequestResource;
use App\Models\Customer;
use App\Models\DataChangeRequest;
use App\Models\Debt;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\Sale;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDataChangeRequest extends ViewRecord
{
    protected static string $resource = DataChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Setujui Pengajuan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->isAdmin() && $this->record->isPending())
                ->requiresConfirmation()
                ->modalHeading('Setujui Pengajuan Perubahan Data')
                ->modalDescription('Apakah Anda yakin ingin menyetujui pengajuan ini? Data terkait akan diperbarui dan disesuaikan secara otomatis.')
                ->action(function () {
                    try {
                        app(\App\Services\DataChangeRequestService::class)->approveRequest($this->record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Pengajuan Berhasil Disetujui')
                            ->body('Perubahan data telah diterapkan ke sistem.')
                            ->send();

                        $this->refreshFormData(['status', 'reviewed_by', 'reviewed_at']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Menyetujui')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\Action::make('reject')
                ->label('Tolak Pengajuan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => auth()->user()?->isAdmin() && $this->record->isPending())
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
                ->action(function (array $data) {
                    try {
                        app(\App\Services\DataChangeRequestService::class)->rejectRequest(
                            $this->record,
                            auth()->user(),
                            $data['rejection_reason']
                        );

                        Notification::make()
                            ->success()
                            ->title('Pengajuan Berhasil Ditolak')
                            ->body('Kasir akan menerima notifikasi alasan penolakan.')
                            ->send();

                        $this->refreshFormData(['status', 'reviewed_by', 'reviewed_at', 'rejection_reason']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Menolak')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pengajuan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')->label('ID Pengajuan')->prefix('#')->weight('bold'),
                            TextEntry::make('created_at')->label('Waktu Diajukan')->dateTime('d M Y H:i'),
                            TextEntry::make('user.name')->label('Kasir Pengaju')->weight('semibold'),
                            TextEntry::make('module_name')
                                ->label('Modul')
                                ->badge()
                                ->color(fn (DataChangeRequest $record) => $record->module_badge_color),
                            TextEntry::make('request_type')
                                ->label('Jenis Permohonan')
                                ->badge()
                                ->color(fn (DataChangeRequest $record) => match ($record->request_type) {
                                    DataChangeRequest::TYPE_EDIT => 'info',
                                    DataChangeRequest::TYPE_VOID => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (DataChangeRequest $record) => $record->request_type_label),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (DataChangeRequest $record) => $record->status_badge_color)
                                ->formatStateUsing(fn (DataChangeRequest $record) => $record->status_label),
                            TextEntry::make('document_number')
                                ->label('No. Nota Terkait')
                                ->weight('bold'),
                        ]),
                        TextEntry::make('reason')
                            ->label('Alasan Diajukan oleh Kasir')
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextEntry::make('reviewer.name')
                                ->label('Ditinjau Oleh')
                                ->visible(fn (DataChangeRequest $record) => (bool) $record->reviewed_by)
                                ->placeholder('-'),
                            TextEntry::make('reviewed_at')
                                ->label('Waktu Ditinjau')
                                ->dateTime('d M Y H:i')
                                ->visible(fn (DataChangeRequest $record) => (bool) $record->reviewed_at)
                                ->placeholder('-'),
                            TextEntry::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->color('danger')
                                ->columnSpanFull()
                                ->visible(fn (DataChangeRequest $record) => $record->isRejected()),
                        ]),
                    ]),

                // 1. Perbandingan Penjualan
                Section::make('Perbandingan Data Penjualan (Asli vs Usulan Kasir)')
                    ->visible(fn (DataChangeRequest $record) => $record->requestable_type === Sale::class)
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Data Saat Ini (Asli)')
                                ->schema([
                                    TextEntry::make('original_data.sale_date')
                                        ->label('Tanggal Penjualan')
                                        ->date('d M Y'),
                                    TextEntry::make('original_data.party_name')
                                        ->label('Pelanggan'),
                                    TextEntry::make('original_data.payment_method_label')
                                        ->label('Metode Pembayaran')
                                        ->badge()
                                        ->color('gray'),
                                    TextEntry::make('original_data.total_amount')
                                        ->label('Total Penjualan')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->weight('bold'),
                                    TextEntry::make('original_items')
                                        ->label('Rincian Barang Asli')
                                        ->state(function (DataChangeRequest $record) {
                                            $items = $record->original_data['items'] ?? [];
                                            if (empty($items)) {
                                                return '-';
                                            }

                                            return collect($items)->map(function ($item) {
                                                $qty = (float) ($item['quantity'] ?? 0);
                                                $price = rupiah($item['price'] ?? 0);
                                                $subtotal = rupiah($item['subtotal'] ?? 0);
                                                $notes = ! empty($item['notes']) ? " ({$item['notes']})" : '';

                                                return "• {$item['product_name']}{$notes} : {$qty} x {$price} = {$subtotal}";
                                            })->implode("\n");
                                        })
                                        ->prose(),
                                ])
                                ->columnSpan(1),

                            Section::make('Usulan Perubahan oleh Kasir')
                                ->schema([
                                    TextEntry::make('proposed_info')
                                        ->label('Status Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_VOID)
                                        ->state('Kasir mengajukan pembatalan penuh (Void) untuk transaksi penjualan ini.')
                                        ->color('danger')
                                        ->weight('bold'),
                                    TextEntry::make('proposed_data.sale_date')
                                        ->label('Tanggal Penjualan Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->date('d M Y'),
                                    TextEntry::make('proposed_party')
                                        ->label('Pelanggan Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->state(function (DataChangeRequest $record) {
                                            $partyId = $record->proposed_data['receivable_party_id'] ?? null;
                                            if (! $partyId) {
                                                return '-';
                                            }

                                            return ReceivableParty::find($partyId)?->name ?? "Pelanggan #{$partyId}";
                                        }),
                                    TextEntry::make('proposed_payment_method')
                                        ->label('Metode Pembayaran Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->badge()
                                        ->color('info')
                                        ->state(fn (DataChangeRequest $record) => $record->proposed_data['payment_method'] ?? '-'),
                                    TextEntry::make('proposed_items')
                                        ->label('Rincian Barang Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->state(function (DataChangeRequest $record) {
                                            $items = $record->proposed_data['items'] ?? [];
                                            if (empty($items)) {
                                                return '-';
                                            }

                                            return collect($items)->map(function ($item) {
                                                $qty = (float) ($item['quantity'] ?? 0);
                                                $price = rupiah($item['price'] ?? 0);
                                                $subtotal = rupiah($item['subtotal'] ?? 0);
                                                $notes = ! empty($item['notes']) ? " ({$item['notes']})" : '';

                                                $pName = $item['product_name'] ?? null;
                                                if (! $pName && ! empty($item['product_id'])) {
                                                    $pName = Product::find($item['product_id'])?->name ?? "Produk #{$item['product_id']}";
                                                }

                                                return "• {$pName}{$notes} : {$qty} x {$price} = {$subtotal}";
                                            })->implode("\n");
                                        })
                                        ->prose(),
                                ])
                                ->columnSpan(1),
                        ]),
                    ]),

                // 2. Perbandingan Hutang Toko
                Section::make('Perbandingan Data Hutang Toko (Asli vs Usulan Kasir)')
                    ->visible(fn (DataChangeRequest $record) => $record->requestable_type === Debt::class)
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Data Hutang Saat Ini (Asli)')
                                ->schema([
                                    TextEntry::make('original_data.invoice_number')
                                        ->label('Nomor Nota'),
                                    TextEntry::make('original_data.customer_name')
                                        ->label('Supplier / Pihak Terkait'),
                                    TextEntry::make('original_data.amount')
                                        ->label('Total Nominal Hutang')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->weight('bold'),
                                    TextEntry::make('original_data.paid_amount')
                                        ->label('Sudah Dibayar')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->color('success'),
                                    TextEntry::make('original_data.remaining_amount')
                                        ->label('Sisa Tagihan')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->color('warning'),
                                    TextEntry::make('original_data.debt_date')
                                        ->label('Tanggal Hutang')
                                        ->date('d M Y'),
                                    TextEntry::make('original_data.due_date')
                                        ->label('Jatuh Tempo')
                                        ->placeholder('-')
                                        ->date('d M Y'),
                                    TextEntry::make('original_data.description')
                                        ->label('Keterangan')
                                        ->placeholder('-'),
                                ])
                                ->columnSpan(1),

                            Section::make('Usulan Perubahan oleh Kasir')
                                ->schema([
                                    TextEntry::make('proposed_info_debt')
                                        ->label('Status Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_VOID)
                                        ->state('Kasir mengajukan pembatalan penuh (Void) untuk nota hutang ini.')
                                        ->color('danger')
                                        ->weight('bold'),
                                    TextEntry::make('proposed_customer')
                                        ->label('Supplier Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->state(function (DataChangeRequest $record) {
                                            $cId = $record->proposed_data['customer_id'] ?? null;
                                            return $cId ? Customer::find($cId)?->name ?? "Supplier #{$cId}" : '-';
                                        }),
                                    TextEntry::make('proposed_data.amount')
                                        ->label('Nominal Hutang Baru')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->weight('bold'),
                                    TextEntry::make('proposed_data.debt_date')
                                        ->label('Tanggal Hutang Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->date('d M Y'),
                                    TextEntry::make('proposed_data.due_date')
                                        ->label('Jatuh Tempo Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->placeholder('-')
                                        ->date('d M Y'),
                                    TextEntry::make('proposed_data.description')
                                        ->label('Keterangan Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->placeholder('-'),
                                ])
                                ->columnSpan(1),
                        ]),
                    ]),

                // 3. Perbandingan Piutang Pelanggan
                Section::make('Perbandingan Data Piutang Pelanggan (Asli vs Usulan Kasir)')
                    ->visible(fn (DataChangeRequest $record) => $record->requestable_type === Receivable::class)
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Data Piutang Saat Ini (Asli)')
                                ->schema([
                                    TextEntry::make('original_data.invoice_number')
                                        ->label('Nomor Nota'),
                                    TextEntry::make('original_data.party_name')
                                        ->label('Pelanggan / Debitur'),
                                    TextEntry::make('original_data.amount')
                                        ->label('Total Nominal Piutang')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->weight('bold'),
                                    TextEntry::make('original_data.paid_amount')
                                        ->label('Sudah Diterima')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->color('success'),
                                    TextEntry::make('original_data.remaining_amount')
                                        ->label('Sisa Tagihan')
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->color('warning'),
                                    TextEntry::make('original_data.receivable_date')
                                        ->label('Tanggal Piutang')
                                        ->date('d M Y'),
                                    TextEntry::make('original_data.due_date')
                                        ->label('Jatuh Tempo')
                                        ->placeholder('-')
                                        ->date('d M Y'),
                                    TextEntry::make('original_data.description')
                                        ->label('Keterangan')
                                        ->placeholder('-'),
                                ])
                                ->columnSpan(1),

                            Section::make('Usulan Perubahan oleh Kasir')
                                ->schema([
                                    TextEntry::make('proposed_info_receivable')
                                        ->label('Status Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_VOID)
                                        ->state('Kasir mengajukan pembatalan penuh (Void) untuk nota piutang ini.')
                                        ->color('danger')
                                        ->weight('bold'),
                                    TextEntry::make('proposed_party_receivable')
                                        ->label('Pelanggan Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->state(function (DataChangeRequest $record) {
                                            $pId = $record->proposed_data['receivable_party_id'] ?? null;
                                            return $pId ? ReceivableParty::find($pId)?->name ?? "Pelanggan #{$pId}" : '-';
                                        }),
                                    TextEntry::make('proposed_data.amount')
                                        ->label('Nominal Piutang Baru')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->formatStateUsing(fn ($state) => rupiah($state))
                                        ->weight('bold'),
                                    TextEntry::make('proposed_data.receivable_date')
                                        ->label('Tanggal Piutang Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->date('d M Y'),
                                    TextEntry::make('proposed_data.due_date')
                                        ->label('Jatuh Tempo Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->placeholder('-')
                                        ->date('d M Y'),
                                    TextEntry::make('proposed_data.description')
                                        ->label('Keterangan Usulan')
                                        ->visible(fn (DataChangeRequest $record) => $record->request_type === DataChangeRequest::TYPE_EDIT)
                                        ->placeholder('-'),
                                ])
                                ->columnSpan(1),
                        ]),
                    ]),
            ]);
    }
}
