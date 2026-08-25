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
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace, 'Arial Narrow', sans-serif;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            margin: 0;
            padding-top: 20mm;
            padding-left: 7mm;
            padding-right: 7mm;
            padding-bottom: 4mm;
            line-height: 1.25;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }

        .header-section {
            text-align: center;
            margin-bottom: 3px;
            page-break-inside: avoid;
        }

        .shop-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: -0.3px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 3px 0;
        }

        /* Meta table */
        .meta-section {
            margin-bottom: 3px;
            page-break-inside: avoid;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-table .label-col {
            width: 65px;
        }

        /* Items table with multi-page support */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0;
            page-break-inside: auto;
        }

        table.items-table thead {
            display: table-header-group;
        }

        table.items-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2.5px 1px;
            font-size: 8.5px;
            font-weight: bold;
        }

        table.items-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        table.items-table td {
            padding: 1.5px 1px;
            vertical-align: top;
            font-size: 8.5px;
        }

        .item-name {
            font-weight: bold;
            padding-top: 3px !important;
            padding-bottom: 1px !important;
            font-size: 9px;
        }

        .item-title-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
            padding: 0;
        }

        .item-title-table td {
            padding: 0 !important;
            vertical-align: top;
            border: none !important;
        }

        .item-num {
            width: 20px;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
        }

        .item-text {
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            word-break: break-word;
        }

        .item-notes {
            font-size: 8.5px;
            font-weight: bold;
            color: #000;
            margin-top: 1px;
        }

        .item-sub-row td {
            padding-top: 1px !important;
            padding-bottom: 3px !important;
            font-size: 8.5px;
        }

        /* Totals section */
        .totals-section {
            page-break-inside: avoid;
            margin-top: 3px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }

        .totals-table td {
            padding: 1px 1px;
        }

        .totals-table .grand-row td {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-size: 10px;
            font-weight: bold;
            padding: 2.5px 1px;
        }

        /* Footer section */
        .footer-section {
            text-align: center;
            margin-top: 5px;
            page-break-inside: avoid;
            font-size: 8px;
        }

        .thanks {
            font-size: 9.5px;
            font-weight: bold;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    {{-- Header Toko --}}
    <div class="header-section">
        <div class="shop-name">TOKO KAIN BU ULIL</div>
    </div>

    <hr class="divider">

    {{-- Info Transaksi --}}
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="label-col">No. Nota</td>
                <td>: {{ $sale->transaction_number }}</td>
            </tr>
            <tr>
                <td class="label-col">Tanggal</td>
                <td>: {{ $sale->sale_date?->format('d/m/Y') }} {{ $sale->created_at?->format('H:i') }}</td>
            </tr>
            <tr>
                <td class="label-col">Kasir</td>
                <td>: {{ $sale->creator?->name ?: '-' }}</td>
            </tr>
            @if ($sale->party)
                <tr>
                    <td class="label-col">Pelanggan</td>
                    <td>: {{ $sale->party->name }} {{ $sale->party->phone ? '('.$sale->party->phone.')' : '' }}</td>
                </tr>
            @endif
            <tr>
                <td class="label-col">Pembayaran</td>
                <td>: {{ $sale->payment_method_label }}</td>
            </tr>
            @if ($sale->receivable)
                <tr>
                    <td class="label-col">No. Piutang</td>
                    <td>: {{ $sale->receivable->invoice_number }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Daftar Item Barang (Format 2-baris per item) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="left" style="width: 48%;">Nama Produk</th>
                <th class="center" style="width: 14%;">Qty</th>
                <th class="right" style="width: 18%;">Harga</th>
                <th class="right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $index => $item)
                <tr>
                    <td class="left item-name" colspan="4">
                        <table class="item-title-table">
                            <tr>
                                <td class="item-num">{{ $index + 1 }}.</td>
                                <td class="item-text">
                                    {{ $item->product_name }}
                                    @if (! empty($item->notes))
                                        <div class="item-notes">
                                            * {{ $item->notes }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="item-sub-row">
                    <td class="left"></td>
                    <td class="center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : $item->quantity }}</td>
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
                    <td>Bayar Tunai</td>
                    <td class="right">{{ number_format($sale->cash_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Bayar Transfer</td>
                    <td class="right">{{ number_format($sale->transfer_amount, 0, ',', '.') }}</td>
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
        <div class="thanks">-- Terima Kasih Telah Berbelanja --</div>
        <div>Toko Kain Bu Ulil · Cetak: {{ $printedAt }}</div>
    </div>
</body>
</html>
