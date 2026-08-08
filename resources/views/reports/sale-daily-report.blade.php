<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan Harian - {{ $report['date'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 20px;
        }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .brand h1 { margin: 0; font-size: 18px; color: #0d9488; }
        .brand p { margin: 2px 0 0; font-size: 11px; color: #6b7280; }
        .meta { text-align: right; font-size: 10px; color: #6b7280; }
        .meta div { margin-bottom: 2px; }
        h2 { font-size: 14px; margin: 0 0 4px; }
        .subtitle { color: #6b7280; font-size: 11px; margin-bottom: 12px; }

        .summary { display: flex; gap: 8px; margin-bottom: 16px; }
        .card { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
        .card .label { font-size: 9px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .card .value { font-size: 14px; font-weight: bold; color: #111827; }
        .card .value.danger { color: #dc2626; }
        .card .value.success { color: #059669; }
        .card .value.teal { color: #0d9488; }
        .card .value.info { color: #2563eb; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        thead th {
            background: #0d9488;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }
        .center { text-align: center; }

        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; display: flex; justify-content: space-between; }
        .note { font-size: 9px; color: #9ca3af; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <h1>Toko Kain Bu Ulil</h1>
            <p>Laporan Penjualan Harian</p>
        </div>
        <div class="meta">
            <div>Tanggal: {{ $report['date'] }}</div>
            <div>Dibuat oleh: {{ $generatedBy }}</div>
        </div>
    </div>

    @php($summary = $report['summary'])

    <div class="summary">
        <div class="card">
            <div class="label">Transaksi</div>
            <div class="value">{{ $summary['transactions'] }}</div>
        </div>
        <div class="card">
            <div class="label">Item Terjual</div>
            <div class="value">{{ $summary['items_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">Total Penjualan</div>
            <div class="value teal">{{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Tunai</div>
            <div class="value success">{{ number_format($summary['cash_revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Transfer</div>
            <div class="value info">{{ number_format($summary['transfer_revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Kredit (Piutang)</div>
            <div class="value danger">{{ number_format($summary['receivable_revenue'], 0, ',', '.') }}</div>
        </div>
    </div>

    <h2>Daftar Transaksi</h2>
    <div class="subtitle">Total: {{ $summary['transactions'] }} transaksi</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Transaksi</th>
                <th>Jam</th>
                <th>Metode</th>
                <th>Pelanggan</th>
                <th class="center">Item</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['sales'] as $index => $sale)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $sale['transaction_number'] }}</strong></td>
                    <td>{{ $sale['time'] }}</td>
                    <td>{{ $sale['payment_method_label'] }}</td>
                    <td>{{ $sale['party'] ?: '-' }}</td>
                    <td class="center">{{ $sale['items_count'] }}</td>
                    <td class="right">{{ number_format($sale['total_amount'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:16px;color:#6b7280;">
                        Belum ada transaksi pada tanggal ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Rekap Produk Terjual</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th class="center">Jumlah Terjual</th>
                <th class="right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['items'] as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td class="center">{{ $item['quantity'] }}</td>
                    <td class="right">{{ number_format($item['revenue'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:16px;color:#6b7280;">
                        Tidak ada produk terjual pada tanggal ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Dicetak pada: {{ $generatedAt }}</span>
        <span>© {{ date('Y') }} Toko Kain Bu Ulil</span>
    </div>
    <div class="note">
        * Dokumen ini dibuat otomatis oleh sistem. Jumlah tercantum dalam Rupiah.
    </div>
</body>
</html>
