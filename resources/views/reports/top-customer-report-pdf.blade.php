<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pelanggan Terbanyak (Top Customers)</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 16px 20px;
        }
        .header {
            border-bottom: 2.5px solid #0d9488;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .brand h1 {
            margin: 0;
            font-size: 17px;
            color: #0d9488;
        }
        .brand p {
            margin: 2px 0 0;
            font-size: 11px;
            color: #4b5563;
        }
        .meta {
            text-align: right;
            font-size: 9.5px;
            color: #4b5563;
            line-height: 1.4;
        }

        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .summary-box td {
            padding: 4px 8px;
            font-size: 9.5px;
            vertical-align: middle;
            border: none;
        }
        .summary-label {
            color: #64748b;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .summary-val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .summary-val.teal {
            color: #0d9488;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.data-table thead th {
            background: #0d9488;
            color: #ffffff;
            padding: 6px 5px;
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        table.data-table tbody td {
            padding: 5px 5px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            color: #1e293b;
        }
        table.data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .rank-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            background: #e2e8f0;
            color: #334155;
        }
        .rank-1 { background: #fef08a; color: #854d0e; }
        .rank-2 { background: #e2e8f0; color: #334155; }
        .rank-3 { background: #fed7aa; color: #9a3412; }

        .footer {
            margin-top: 18px;
            font-size: 8.5px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer td {
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td class="brand" style="vertical-align: top;">
                    <h1>Toko Kain Bu Ulil</h1>
                    <p>Laporan Pelanggan dengan Transaksi Terbanyak</p>
                </td>
                <td class="meta" style="vertical-align: top; width: 45%;">
                    <div><strong>Periode:</strong> {{ $report['filters']['period_label'] }}</div>
                    <div><strong>Urutan:</strong> {{ $report['filters']['sort_label'] }}</div>
                    <div><strong>Dicetak pada:</strong> {{ $generatedAt }}</div>
                    <div><strong>Oleh:</strong> {{ $generatedBy }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php($summary = $report['summary'])

    <div class="summary-box">
        <table>
            <tr>
                <td>
                    <div class="summary-label">Pelanggan Aktif</div>
                    <div class="summary-val">{{ number_format($summary['active_customers_count']) }} orang</div>
                </td>
                <td>
                    <div class="summary-label">Transaksi Member</div>
                    <div class="summary-val">{{ number_format($summary['customer_transactions']) }} nota</div>
                </td>
                <td>
                    <div class="summary-label">Omset Dari Member</div>
                    <div class="summary-val teal">Rp {{ number_format($summary['customer_revenue'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="summary-label">Pelanggan Umum (Walk-in)</div>
                    <div class="summary-val">{{ number_format($summary['walk_in_transactions']) }} nota (Rp {{ number_format($summary['walk_in_revenue'], 0, ',', '.') }})</div>
                </td>
                <td>
                    <div class="summary-label">Total Omset Toko</div>
                    <div class="summary-val">Rp {{ number_format($summary['grand_total_revenue'], 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="center" style="width: 5%;">No</th>
                <th style="width: 20%;">Nama Pelanggan</th>
                <th style="width: 14%;">No. HP / WA</th>
                <th class="center" style="width: 10%;">Nota</th>
                <th class="right" style="width: 14%;">Total Belanja</th>
                <th class="center" style="width: 8%;">Qty</th>
                <th class="right" style="width: 12%;">Rata-rata/Nota</th>
                <th class="center" style="width: 9%;">Terakhir</th>
                <th class="right" style="width: 8%;">Sisa Piutang</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['customers'] as $cust)
                <tr>
                    <td class="center">
                        <span class="rank-badge {{ $cust['rank'] === 1 ? 'rank-1' : ($cust['rank'] === 2 ? 'rank-2' : ($cust['rank'] === 3 ? 'rank-3' : '')) }}">
                            #{{ $cust['rank'] }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $cust['name'] }}</strong>
                        @if ($cust['is_deleted'])
                            <span style="font-size: 7.5px; color: #dc2626;">(Nonaktif)</span>
                        @endif
                        @if ($cust['address'])
                            <div style="font-size: 8px; color: #64748b;">{{ Str::limit($cust['address'], 30) }}</div>
                        @endif
                    </td>
                    <td>{{ $cust['phone'] ?: '-' }}</td>
                    <td class="center bold">{{ number_format($cust['transaction_count']) }}</td>
                    <td class="right bold">Rp {{ number_format($cust['total_spent'], 0, ',', '.') }}</td>
                    <td class="center">{{ formatQuantity($cust['total_quantity']) }}</td>
                    <td class="right">Rp {{ number_format($cust['average_spent'], 0, ',', '.') }}</td>
                    <td class="center">{{ $cust['last_sale_date_formatted'] }}</td>
                    <td class="right" style="{{ $cust['unpaid_receivable'] > 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #16a34a;' }}">
                        {{ $cust['unpaid_receivable'] > 0 ? 'Rp ' . number_format($cust['unpaid_receivable'], 0, ',', '.') : 'Lunas' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 18px; color: #64748b;">
                        Tidak ada data transaksi pelanggan pada filter periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td>Laporan Pelanggan Toko Kain Bu Ulil</td>
                <td class="right">Halaman 1 &middot; Sistem Manajemen Toko Kain</td>
            </tr>
        </table>
    </div>
</body>
</html>
