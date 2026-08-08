<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 20px;
        }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .brand h1 { margin: 0; font-size: 18px; color: #4f46e5; }
        .brand p { margin: 2px 0 0; font-size: 11px; color: #6b7280; }
        .meta { text-align: right; font-size: 10px; color: #6b7280; }
        .meta div { margin-bottom: 2px; }
        h2 { font-size: 14px; margin: 0 0 4px; }
        .subtitle { color: #6b7280; font-size: 11px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #4f46e5;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }
        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; display: flex; justify-content: space-between; }
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <h1>Manajemen Hutang Pelanggan</h1>
            <p>Laporan {{ $title }}</p>
        </div>
        <div class="meta">
            <div>Periode: {{ $period }}</div>
            <div>Dibuat oleh: {{ $generatedBy }}</div>
        </div>
    </div>

    <h2>{{ $title }}</h2>
    <div class="subtitle">Total data: {{ $rows->count() }} baris</div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ mb_strtoupper($column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $exporter = $exporter ?? \App\Exports\ReportExport::class;
                $reportType = $type ?? 'debt_list';
                $rightColumns = $rightColumns ?? [
                    'Nominal', 'Sudah Dibayar', 'Sisa Hutang', 'Sudah Diterima', 'Sisa Piutang',
                    'Total', 'Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo',
                    'Total Sisa Hutang', 'Total Sisa Piutang',
                ];
            @endphp
            @forelse ($rows as $row)
                <tr>
                    @foreach ($exporter::valuesFor($reportType, $row) as $index => $value)
                        <td @class(['right' => in_array($columns[$index] ?? null, $rightColumns, true)])>
                            @if (str_contains($columns[$index] ?? '', 'Status'))
                                <span class="badge {{ str_contains($value, 'Lunas') ? 'badge-success' : 'badge-danger' }}">{{ $value }}</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center;padding:16px;color:#6b7280;">
                        Tidak ada data untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Dicetak pada: {{ $generatedAt }}</span>
        <span>© {{ date('Y') }} Manajemen Hutang</span>
    </div>
</body>
</html>
