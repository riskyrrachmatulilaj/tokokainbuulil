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
            size: 120mm 140mm;
            margin: 5mm 6mm 6mm 6mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace, 'Arial Narrow', sans-serif;
            font-size: 9.5px;
            font-weight: bold;
            color: #000;
            width: 100%;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }

        .header-section {
            text-align: center;
            margin-bottom: 4px;
            page-break-inside: avoid;
        }

        .shop-name {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: -0.3px;
        }

        .shop-subtitle {
            font-size: 9px;
            font-weight: bold;
            margin-top: 1px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .divider-double {
            border: none;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            height: 2px;
            margin: 4px 0;
        }

        /* Meta table */
        .meta-section {
            margin-bottom: 4px;
            page-break-inside: avoid;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-table .label-col {
            width: 75px;
        }

        /* Items table with multi-page support */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
            page-break-inside: auto;
        }

        table.items-table thead {
            display: table-header-group;
        }

        table.items-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 2px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }

        table.items-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.items-table td {
            padding: 2px 2px;
            vertical-align: top;
            font-size: 9px;
        }

        /* Totals section */
        .totals-section {
            page-break-inside: avoid;
            margin-top: 4px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .totals-table td {
            padding: 1.5px 2px;
        }

        .totals-table .grand-row td {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 2px;
        }

        /* Footer section */
        .footer-section {
            text-align: center;
            margin-top: 6px;
            page-break-inside: avoid;
            font-size: 8.5px;
        }

        .thanks {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
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

    {{-- Info Transaksi --}}
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="label-col">No. Nota</td>
                <td>: {{ $sale->transaction_number }}</td>
                <td class="right">{{ $sale->sale_date?->format('d/m/Y') }} {{ $sale->created_at?->format('H:i') }}</td>
            </tr>
            <tr>
                <td class="label-col">Kasir</td>
                <td>: {{ $sale->creator?->name ?: '-' }}</td>
                <td class="right">Metode: {{ $sale->payment_method_label }}</td>
            </tr>
            @if ($sale->party)
                <tr>
                    <td class="label-col">Pelanggan</td>
                    <td colspan="2">: {{ $sale->party->name }} {{ $sale->party->phone ? '('.$sale->party->phone.')' : '' }}</td>
                </tr>
            @endif
            @if ($sale->receivable)
                <tr>
                    <td class="label-col">No. Piutang</td>
                    <td colspan="2">: {{ $sale->receivable->invoice_number }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Daftar Item Barang --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15px;">No</th>
                <th>Nama Produk</th>
                <th class="right" style="width: 35px;">Qty</th>
                <th class="right" style="width: 65px;">Harga</th>
                <th class="right" style="width: 75px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider">

    {{-- Bagian Total --}}
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Total Belanja ({{ $sale->items->sum('quantity') }} item)</td>
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
                <td>TOTAL AKHIR</td>
                <td class="right">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer-section">
        <div class="thanks">-- Terima Kasih Telah Berbelanja --</div>
        <div>Toko Kain Bu Ulil · Cetak: {{ $printedAt }}</div>
    </div>
</body>
</html>
