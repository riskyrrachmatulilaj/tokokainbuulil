<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $sale->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            color: #000;
            width: 72mm;
            padding: 4mm;
            line-height: 1.4;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .separator {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .separator-double {
            border: none;
            border-top: 2px solid #000;
            margin: 4px 0;
        }

        /* Header Toko */
        .shop-name {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
        }
        .shop-subtitle {
            font-size: 9px;
            text-align: center;
            margin-bottom: 6px;
        }

        /* Info transaksi */
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
        }
        .info-row .label {
            color: #333;
        }

        /* Item table */
        .item-name {
            font-size: 9px;
            font-weight: bold;
            display: block;
        }
        .item-detail {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            padding-left: 8px;
        }

        /* Totals */
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }
        .total-row.grand {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8px;
            color: #555;
            margin-top: 6px;
        }
        .thanks {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin: 6px 0 2px;
        }
    </style>
</head>
<body>
    {{-- Header Toko --}}
    <div class="shop-name">Toko Kain Bu Ulil</div>
    <div class="shop-subtitle">NOTA PENJUALAN</div>

    <hr class="separator-double">

    {{-- Info Transaksi --}}
    <div class="info-row">
        <span class="label">No</span>
        <span>{{ $sale->transaction_number }}</span>
    </div>
    <div class="info-row">
        <span class="label">Tgl</span>
        <span>{{ $sale->sale_date?->format('d/m/Y') }} {{ $sale->created_at?->format('H:i') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Kasir</span>
        <span>{{ $sale->creator?->name ?: '-' }}</span>
    </div>
    @if ($sale->party)
        <div class="info-row">
            <span class="label">Pelanggan</span>
            <span>{{ $sale->party->name }}</span>
        </div>
    @endif
    <div class="info-row">
        <span class="label">Bayar</span>
        <span>{{ $sale->payment_method_label }}</span>
    </div>

    <hr class="separator">

    {{-- Daftar Item --}}
    @foreach ($sale->items as $item)
        <div style="margin-bottom: 3px;">
            <span class="item-name">{{ $item->product_name }}</span>
            <div class="item-detail">
                <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
        </div>
    @endforeach

    <hr class="separator">

    {{-- Total --}}
    <div class="total-row">
        <span>Subtotal</span>
        <span>{{ number_format($sale->total_amount, 0, ',', '.') }}</span>
    </div>

    @if ($sale->payment_method === 'cash')
        <div class="total-row">
            <span>Tunai</span>
            <span>{{ number_format($sale->received_amount, 0, ',', '.') }}</span>
        </div>
        <div class="total-row">
            <span>Kembali</span>
            <span>{{ number_format($sale->change_amount, 0, ',', '.') }}</span>
        </div>
    @elseif ($sale->payment_method === 'transfer')
        <div class="total-row">
            <span>Status</span>
            <span>Transfer</span>
        </div>
    @else
        <div class="total-row">
            <span>Status</span>
            <span>Kredit (Piutang)</span>
        </div>
        @if ($sale->receivable)
            <div class="total-row">
                <span>No. Piutang</span>
                <span>{{ $sale->receivable->invoice_number }}</span>
            </div>
        @endif
    @endif

    <hr class="separator-double">

    <div class="total-row grand">
        <span>TOTAL</span>
        <span>{{ number_format($sale->total_amount, 0, ',', '.') }}</span>
    </div>

    <hr class="separator-double">

    {{-- Footer --}}
    <div class="thanks">Terima kasih!</div>
    <div class="footer">
        Toko Kain Bu Ulil<br>
        {{ $printedAt }} · {{ $printedBy }}
    </div>
    <div class="footer" style="margin-top: 4px; font-size: 7px;">
        * Nota dibuat otomatis oleh sistem
    </div>
</body>
</html>
