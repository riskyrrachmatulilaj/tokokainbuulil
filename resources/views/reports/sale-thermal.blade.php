<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $sale->transaction_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            color: #000;
        }

        @page {
            size: 95mm 140mm;
            margin: 2mm 3mm 3mm 3mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace, 'Arial Narrow', sans-serif;
            font-size: 8.5px;
            font-weight: bold;
            color: #000;
            width: 100%;
            margin: 0;
            padding: 0;
            line-height: 1.15;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }

        .header-section {
            text-align: center;
            margin-bottom: 2px;
            page-break-inside: avoid;
        }

        .shop-name {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: -0.3px;
        }

        .shop-subtitle {
            font-size: 8px;
            font-weight: bold;
        }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 2px 0;
        }

        /* Meta compact 2 columns */
        .meta-section {
            margin-bottom: 2px;
            page-break-inside: avoid;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .meta-table td {
            padding: 0.5px 0;
            vertical-align: top;
        }

        /* Items table - 1 line per item */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
            page-break-inside: auto;
        }

        table.items-table thead {
            display: table-header-group;
        }

        table.items-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 1.5px 1px;
            font-size: 8px;
            font-weight: bold;
        }

        table.items-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.items-table td {
            padding: 1px 1px;
            vertical-align: top;
            font-size: 8px;
        }

        .product-col {
            word-break: break-word;
            line-height: 1.1;
        }

        /* Totals section */
        .totals-section {
            page-break-inside: avoid;
            margin-top: 2px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .totals-table td {
            padding: 0.5px 1px;
        }

        .totals-table .grand-row td {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-size: 9.5px;
            font-weight: bold;
            padding: 2px 1px;
        }

        /* Footer section */
        .footer-section {
            text-align: center;
            margin-top: 3px;
            page-break-inside: avoid;
            font-size: 7.5px;
        }
    </style>
</head>
<body>
    {{-- Header Toko --}}
    <div class="header-section">
        <div class="shop-name">TOKO KAIN BU ULIL</div>
        <div class="shop-subtitle">NOTA PENJUALAN</div>
    </div>

    <hr class="divider">

    {{-- Info Transaksi (Ringkas 2 Kolom) --}}
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td style="width: 55%;">No: {{ $sale->transaction_number }}</td>
                <td class="right" style="width: 45%;">{{ $sale->sale_date?->format('d/m/y') }} {{ $sale->created_at?->format('H:i') }}</td>
            </tr>
            <tr>
                <td>Kasir: {{ Str::limit($sale->creator?->name ?: '-', 14) }}</td>
                <td class="right">{{ $sale->payment_method_label }}</td>
            </tr>
            @if ($sale->party)
                <tr>
                    <td colspan="2">Plg: {{ Str::limit($sale->party->name, 22) }} {{ $sale->party->phone ? '('.$sale->party->phone.')' : '' }}</td>
                </tr>
            @endif
            @if ($sale->receivable)
                <tr>
                    <td colspan="2">No. Piutang: {{ $sale->receivable->invoice_number }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Daftar Item Barang (Format 1-baris per item hemat kertas) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="left" style="width: 46%;">Produk</th>
                <th class="center" style="width: 10%;">Qty</th>
                <th class="right" style="width: 22%;">Harga</th>
                <th class="right" style="width: 22%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="left product-col">{{ $item->product_name }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider">

    {{-- Bagian Total Ringkas --}}
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal ({{ $sale->items->sum('quantity') }} item)</td>
                <td class="right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if ($sale->payment_method === 'cash')
                <tr>
                    <td>Tunai Diterima</td>
                    <td class="right">{{ number_format($sale->received_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td class="right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
                </tr>
            @elseif ($sale->payment_method === 'split')
                <tr>
                    <td>Tunai / Transfer</td>
                    <td class="right">{{ number_format($sale->cash_amount, 0, ',', '.') }} / {{ number_format($sale->transfer_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Kembalian</td>
                    <td class="right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
                </tr>
            @elseif ($sale->payment_method === 'transfer')
                <tr>
                    <td>Status Bayar</td>
                    <td class="right">LUNAS (Transfer)</td>
                </tr>
            @else
                <tr>
                    <td>Status Bayar</td>
                    <td class="right">KREDIT (Piutang)</td>
                </tr>
            @endif
            <tr class="grand-row">
                <td>TOTAL</td>
                <td class="right">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer-section">
        <div>-- Terima Kasih Telah Berbelanja --</div>
        <div>Toko Kain Bu Ulil</div>
    </div>
</body>
</html>
