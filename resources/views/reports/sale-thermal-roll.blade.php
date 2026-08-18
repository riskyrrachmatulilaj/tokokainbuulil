<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $sale->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-weight: bold; color: #000; }
        body {
            font-family: 'Arial Narrow', 'Consolas', 'Courier New', monospace;
            font-size: 10px;
            font-weight: bold;
            font-stretch: condensed;
            letter-spacing: -0.4px;
            color: #000;
            width: 72mm;
            padding: 4mm;
            line-height: 1.3;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .separator, .separator-double {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        /* Header Toko */
        .shop-name {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: -0.5px;
            text-align: center;
            margin-bottom: 2px;
        }
        .shop-subtitle {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: -0.3px;
            text-align: center;
            margin-bottom: 6px;
        }

        /* Info transaksi */
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
        }
        .info-row .label {
            color: #000;
            font-weight: bold;
        }

        /* Item table */
        .item-name {
            font-size: 10px;
            font-weight: bold;
            display: block;
        }
        .item-detail {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            padding-left: 8px;
        }

        /* Totals */
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            font-weight: bold;
        }
        .total-row.grand {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: -0.5px;
            margin: 3px 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            margin-top: 6px;
        }
        .thanks {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin: 6px 0 2px;
        }
    </style>
</head>
<body>
    {{-- Header Toko --}}
    <div class="shop-name">Toko Kain Bu Ulil</div>
    <div class="shop-subtitle">STRUK THERMAL ROLL (72mm)</div>

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
        @php
            $qtyFormatted = (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : $item->quantity;
        @endphp
        <div style="margin-bottom: 3px;">
            <span class="item-name">{{ $item->product_name }}</span>
            @if (! empty($item->notes))
                <div style="font-size: 8.5px; font-style: italic; font-weight: normal; padding-left: 6px; color: #333;">
                    ↳ {{ $item->notes }}
                </div>
            @endif
            <div class="item-detail">
                <span>{{ $qtyFormatted }} x {{ number_format($item->price, 0, ',', '.') }}</span>
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
    @elseif ($sale->payment_method === 'split')
        <div class="total-row">
            <span>Tunai</span>
            <span>{{ number_format($sale->cash_amount, 0, ',', '.') }}</span>
        </div>
        <div class="total-row">
            <span>Transfer</span>
            <span>{{ number_format($sale->transfer_amount, 0, ',', '.') }}</span>
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
