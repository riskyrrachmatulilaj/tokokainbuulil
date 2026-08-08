<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rincian Hutang - {{ $customer->name }}</title>
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

        .customer-box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 14px; }
        .customer-box .name { font-size: 15px; font-weight: bold; color: #111827; margin-bottom: 4px; }
        .customer-box .info { color: #374151; font-size: 11px; }
        .customer-box .info span { color: #6b7280; }

        .summary { display: flex; gap: 8px; margin-bottom: 16px; }
        .card { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
        .card .label { font-size: 9px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .card .value { font-size: 14px; font-weight: bold; color: #111827; }
        .card .value.danger { color: #dc2626; }
        .card .value.success { color: #059669; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        thead th {
            background: #4f46e5;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .payments { margin-top: 4px; padding-top: 4px; border-top: 1px dashed #d1d5db; }
        .payments .p-title { font-size: 9px; font-weight: bold; color: #6b7280; margin-bottom: 3px; }
        .payments table { margin-bottom: 0; }
        .payments thead th { background: #6b7280; padding: 4px 5px; font-size: 8px; }
        .payments tbody td { padding: 4px 5px; font-size: 9px; }
        .payments tbody tr:nth-child(even) { background: #f3f4f6; }

        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; display: flex; justify-content: space-between; }
        .note { font-size: 9px; color: #9ca3af; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <h1>Manajemen Hutang Pelanggan</h1>
            <p>Rincian Hutang Pelanggan</p>
        </div>
        <div class="meta">
            <div>Dicetak pada: {{ $generatedAt }}</div>
            <div>Dibuat oleh: {{ $generatedBy }}</div>
        </div>
    </div>

    <div class="customer-box">
        <div class="name">{{ $customer->name }}</div>
        <div class="info">
            <span>Telepon:</span> {{ $customer->phone ?: '-' }} &nbsp;|&nbsp;
            <span>Alamat:</span> {{ $customer->address ?: '-' }}
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="label">Jumlah Nota</div>
            <div class="value">{{ $summary['total_notes'] }}</div>
        </div>
        <div class="card">
            <div class="label">Nota Belum Lunas</div>
            <div class="value danger">{{ $summary['unpaid_notes'] }}</div>
        </div>
        <div class="card">
            <div class="label">Total Hutang</div>
            <div class="value">{{ number_format($summary['total_amount'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Sudah Dibayar</div>
            <div class="value success">{{ number_format($summary['total_paid'], 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Sisa Hutang</div>
            <div class="value {{ $summary['total_remaining'] > 0 ? 'danger' : 'success' }}">{{ number_format($summary['total_remaining'], 0, ',', '.') }}</div>
        </div>
    </div>

    <h2>Daftar Nota &amp; Rincian Pembayaran</h2>
    <div class="subtitle">Total nota: {{ $summary['total_notes'] }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Nota</th>
                <th>Tanggal</th>
                <th>Jatuh Tempo</th>
                <th class="right">Total</th>
                <th class="right">Dibayar</th>
                <th class="right">Sisa</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($debts as $index => $debt)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $debt->invoice_number }}</strong>
                        @if ($debt->description)
                            <br><small style="color:#6b7280;">{{ $debt->description }}</small>
                        @endif
                    </td>
                    <td>{{ $debt->debt_date?->format('d M Y') }}</td>
                    <td>{{ $debt->due_date?->format('d M Y') ?: '-' }}</td>
                    <td class="right">{{ number_format($debt->amount, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($debt->paid_amount, 0, ',', '.') }}</td>
                    <td class="right"><strong>{{ number_format($debt->remaining_amount, 0, ',', '.') }}</strong></td>
                    <td>
                        <span class="badge {{ $debt->status === 'paid' ? 'badge-success' : 'badge-danger' }}">{{ $debt->status_label }}</span>
                    </td>
                </tr>
                @if ($debt->paymentHistories->isNotEmpty())
                    <tr>
                        <td colspan="8" style="padding:0;">
                            <div class="payments">
                                <div class="p-title">RINCIAN PEMBAYARAN</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>No. Transaksi</th>
                                            <th>Jenis</th>
                                            <th>Keterangan</th>
                                            <th class="right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($debt->paymentHistories as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                                <td>{{ $payment->transaction_number }}</td>
                                                <td>{{ $payment->payment_type_label }}</td>
                                                <td>{{ $payment->description ?: '-' }}</td>
                                                <td class="right">{{ number_format($payment->amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:16px;color:#6b7280;">
                        Pelanggan tidak memiliki nota hutang.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Dicetak pada: {{ $generatedAt }}</span>
        <span>© {{ date('Y') }} Manajemen Hutang</span>
    </div>
    <div class="note">
        * Dokumen ini dibuat otomatis oleh sistem dan tidak memerlukan tanda tangan. Jumlah tercantum dalam Rupiah.
    </div>
</body>
</html>
