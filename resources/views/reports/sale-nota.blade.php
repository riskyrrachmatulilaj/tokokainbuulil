<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota Penjualan - {{ $sale->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            margin: 12mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 14px; page-break-inside: avoid; }
        .brand h1 { margin: 0; font-size: 18px; color: #0d9488; }
        .brand p { margin: 2px 0 0; font-size: 11px; color: #6b7280; }
        .nota-no { text-align: right; }
        .nota-no .label { font-size: 9px; text-transform: uppercase; color: #6b7280; }
        .nota-no .value { font-size: 16px; font-weight: bold; color: #111827; }

        .meta { margin-bottom: 12px; font-size: 11px; page-break-inside: avoid; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .k { color: #6b7280; width: 110px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; page-break-inside: auto; }
        table.items thead { display: table-header-group; }
        table.items thead th {
            background: #0d9488;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.items tbody tr { page-break-inside: avoid; page-break-after: auto; }
        table.items tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        table.items tbody tr:nth-child(even) { background: #f9fafb; }
        .right { text-align: right; }

        .total-box { margin-left: auto; width: 260px; page-break-inside: avoid; }
        .total-box table { width: 100%; border-collapse: collapse; }
        .total-box td { padding: 4px 6px; }
        .total-box .grand td { font-size: 14px; font-weight: bold; background: #0d9488; color: #fff; border-radius: 4px; }

        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .thanks { text-align: center; margin-top: 18px; font-size: 12px; color: #0d9488; font-weight: bold; page-break-inside: avoid; }
        .note { font-size: 9px; color: #9ca3af; margin-top: 8px; page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <h1>Toko Kain Bu Ulil</h1>
            <p>Nota Penjualan</p>
        </div>
        <div class="nota-no">
            <div class="label">No. Transaksi</div>
            <div class="value">{{ $sale->transaction_number }}</div>
        </div>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="k">Tanggal</td>
                <td>: {{ $sale->sale_date?->format('d M Y') }} ({{ $sale->created_at?->format('H:i') }})</td>
            </tr>
            @if ($sale->party)
                <tr>
                    <td class="k">Pelanggan</td>
                    <td>: {{ $sale->party->name }}</td>
                </tr>
            @endif
            <tr>
                <td class="k">Metode Pembayaran</td>
                <td>: {{ $sale->payment_method_label }}</td>
            </tr>
            @if ($sale->receivable)
                <tr>
                    <td class="k">Nota Piutang</td>
                    <td>: {{ $sale->receivable->invoice_number }}</td>
                </tr>
            @endif
            @if ($sale->description)
                <tr>
                    <td class="k">Keterangan</td>
                    <td>: {{ $sale->description }}</td>
                </tr>
            @endif
            <tr>
                <td class="k">Kasir</td>
                <td>: {{ $sale->creator?->name ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th class="right">Harga</th>
                <th class="right">Jumlah</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div>{{ $item->product_name }}</div>
                        @if (! empty($item->notes))
                            <div style="font-size: 8pt; color: #444; font-style: italic; margin-top: 2px;">
                                ↳ {{ $item->notes }}
                            </div>
                        @endif
                    </td>
                    <td class="right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : $item->quantity }}</td>
                    <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <table>
            <tr>
                <td>Total Belanja</td>
                <td class="right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if ($sale->payment_method === 'cash')
                <tr>
                    <td>Uang Diterima</td>
                    <td class="right">{{ number_format($sale->received_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td class="right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
                </tr>
            @elseif ($sale->payment_method === 'split')
                <tr>
                    <td>Bayar Tunai</td>
                    <td class="right">{{ number_format($sale->cash_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Bayar Transfer</td>
                    <td class="right">{{ number_format($sale->transfer_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Total Dibayar</td>
                    <td class="right">{{ number_format($sale->received_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td class="right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
                </tr>
            @elseif ($sale->payment_method === 'transfer')
                <tr>
                    <td>Status</td>
                    <td class="right">Transfer</td>
                </tr>
            @else
                <tr>
                    <td>Status</td>
                    <td class="right">Kredit (Piutang)</td>
                </tr>
            @endif
            <tr class="grand">
                <td>TOTAL</td>
                <td class="right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="thanks">Terima kasih telah berbelanja di Toko Kain Bu Ulil</div>

    <div class="footer">
        <span>Dicetak pada: {{ $printedAt }}</span>
        <span>Oleh: {{ $printedBy }}</span>
    </div>
    <div class="note">
        * Nota ini dibuat otomatis oleh sistem. Jumlah tercantum dalam Rupiah.
    </div>
</body>
</html>
