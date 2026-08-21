@php
    use App\Models\Sale;
@endphp

<x-filament-panels::page>
    <style>
        .kasir-product-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 0.85rem !important;
            max-height: 560px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding-right: 4px !important;
        }

        @media (max-width: 640px) {
            .kasir-product-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .kasir-product {
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            width: 100% !important;
            min-height: 110px !important;
            padding: 0.9rem 1rem !important;
            border: 1px solid var(--kasir-border, rgba(128, 128, 128, 0.25)) !important;
            border-radius: 0.75rem !important;
            background: var(--kasir-surface-solid, #ffffff) !important;
            text-align: left !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        .dark .kasir-product,
        html.dark .kasir-product {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }

        .kasir-product:hover {
            border-color: var(--primary-500, #6366f1) !important;
            background: rgba(99, 102, 241, 0.08) !important;
        }

        .kasir-product.is-out-of-stock {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }

        .kasir-product-top {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.25rem !important;
            width: 100% !important;
        }

        .kasir-product-info {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.2rem !important;
            width: 100% !important;
        }

        .kasir-product-name {
            display: block !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: var(--kasir-text, #111827) !important;
            line-height: 1.35 !important;
            word-break: break-word !important;
            white-space: normal !important;
        }

        .dark .kasir-product-name,
        html.dark .kasir-product-name {
            color: #f9fafb !important;
        }

        .kasir-product-desc {
            display: block !important;
            font-size: 0.775rem !important;
            color: var(--kasir-muted, #6b7280) !important;
            line-height: 1.35 !important;
            word-break: break-word !important;
            white-space: normal !important;
        }

        .dark .kasir-product-desc,
        html.dark .kasir-product-desc {
            color: #9ca3af !important;
        }

        .kasir-product-bottom {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.5rem !important;
            width: 100% !important;
            margin-top: 0.75rem !important;
            padding-top: 0.5rem !important;
            border-top: 1px dashed rgba(128, 128, 128, 0.25) !important;
        }

        .kasir-product-meta-bottom {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.1rem !important;
        }

        .kasir-product-price {
            display: block !important;
            font-size: 0.925rem !important;
            font-weight: 800 !important;
            color: var(--primary-600, #4f46e5) !important;
        }

        .dark .kasir-product-price,
        html.dark .kasir-product-price {
            color: #818cf8 !important;
        }

        .kasir-product-stock-badge {
            display: inline-block !important;
            font-size: 0.725rem !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
        }

        .kasir-product-stock-badge.is-danger { color: #ef4444 !important; }
        .kasir-product-stock-badge.is-warning { color: #f59e0b !important; }
        .kasir-product-stock-badge.is-success { color: #10b981 !important; }

        .kasir-product-add-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0.4rem 0.85rem !important;
            border-radius: 0.5rem !important;
            background: var(--primary-600, #4f46e5) !important;
            color: #ffffff !important;
            font-size: 0.775rem !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }

        /* Modal Backdrop & Container */
        .kasir-modal-backdrop {
            position: fixed !important;
            inset: 0 !important;
            z-index: 9999 !important;
            background: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(4px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
            overflow-y: auto !important;
            animation: kasirModalFade 0.18s ease !important;
        }

        @keyframes kasirModalFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .kasir-modal-container {
            background: #ffffff !important;
            width: 100% !important;
            max-width: 680px !important;
            border-radius: 1rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            animation: kasirModalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        .dark .kasir-modal-container,
        html.dark .kasir-modal-container {
            background: #1e293b !important;
            border-color: rgba(51, 65, 85, 0.8) !important;
            color: #f8fafc !important;
        }

        @keyframes kasirModalPop {
            from { transform: scale(0.95) translateY(10px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .kasir-modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0.85rem 1.25rem !important;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8) !important;
            background: rgba(248, 250, 252, 0.9) !important;
        }

        .dark .kasir-modal-header,
        html.dark .kasir-modal-header {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(51, 65, 85, 0.6) !important;
        }

        .kasir-modal-icon-badge {
            width: 2.25rem !important;
            height: 2.25rem !important;
            border-radius: 0.6rem !important;
            background: rgba(13, 148, 136, 0.12) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .kasir-modal-title {
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            color: #0f172a !important;
        }

        .dark .kasir-modal-title,
        html.dark .kasir-modal-title {
            color: #f8fafc !important;
        }

        .kasir-modal-subtitle {
            font-size: 0.75rem !important;
            color: #64748b !important;
            display: block !important;
        }

        .dark .kasir-modal-subtitle,
        html.dark .kasir-modal-subtitle {
            color: #94a3b8 !important;
        }

        .kasir-draft-badge {
            display: inline-block !important;
            padding: 0.15rem 0.5rem !important;
            border-radius: 9999px !important;
            font-size: 0.6875rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            background: #fef3c7 !important;
            color: #b45309 !important;
            border: 1px solid #fde68a !important;
        }

        .kasir-modal-close-btn {
            border: none !important;
            background: transparent !important;
            cursor: pointer !important;
            padding: 0.35rem !important;
            border-radius: 0.5rem !important;
            color: #64748b !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.15s !important;
        }

        .kasir-modal-close-btn:hover {
            background: rgba(0, 0, 0, 0.06) !important;
            color: #0f172a !important;
        }

        .dark .kasir-modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
        }

        .kasir-modal-body {
            padding: 1.25rem !important;
            max-height: 68vh !important;
            overflow-y: auto !important;
            background: #f1f5f9 !important;
        }

        .dark .kasir-modal-body,
        html.dark .kasir-modal-body {
            background: #0f172a !important;
        }

        /* Authentic A4 Document Paper Simulation */
        .kasir-a4-paper {
            background: #ffffff !important;
            color: #1f2937 !important;
            font-family: Helvetica, Arial, sans-serif !important;
            font-size: 11px !important;
            padding: 1.75rem 1.5rem !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #e2e8f0 !important;
            line-height: 1.35 !important;
        }

        .kasir-a4-paper * {
            box-sizing: border-box !important;
        }

        .a4-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-bottom: 3px solid #0d9488 !important;
            padding-bottom: 10px !important;
            margin-bottom: 12px !important;
        }

        .a4-brand h1 {
            margin: 0 !important;
            font-size: 18px !important;
            color: #0d9488 !important;
            font-weight: bold !important;
        }

        .a4-brand p {
            margin: 2px 0 0 !important;
            font-size: 11px !important;
            color: #6b7280 !important;
        }

        .a4-nota-no {
            text-align: right !important;
        }

        .a4-nota-no .label {
            font-size: 9px !important;
            text-transform: uppercase !important;
            color: #6b7280 !important;
            font-weight: 600 !important;
        }

        .a4-nota-no .value {
            font-size: 15px !important;
            font-weight: bold !important;
            color: #111827 !important;
        }

        .a4-meta {
            margin-bottom: 12px !important;
            font-size: 11px !important;
        }

        .a4-meta table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .a4-meta td {
            padding: 2.5px 0 !important;
            vertical-align: top !important;
            color: #1f2937 !important;
        }

        .a4-meta .k {
            color: #6b7280 !important;
            width: 130px !important;
            font-weight: 500 !important;
        }

        table.a4-items {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 12px !important;
        }

        table.a4-items thead th {
            background: #0d9488 !important;
            color: #ffffff !important;
            padding: 7px 6px !important;
            text-align: left !important;
            font-size: 9.5px !important;
            text-transform: uppercase !important;
            font-weight: bold !important;
        }

        table.a4-items tbody td {
            padding: 6px !important;
            border-bottom: 1px solid #e5e7eb !important;
            color: #1f2937 !important;
            font-size: 11px !important;
        }

        table.a4-items tbody tr:nth-child(even) {
            background: #f9fafb !important;
        }

        .a4-total-box {
            margin-left: auto !important;
            width: 270px !important;
        }

        .a4-total-box table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .a4-total-box td {
            padding: 3.5px 6px !important;
            font-size: 11px !important;
            color: #1f2937 !important;
        }

        .a4-total-box .grand td {
            font-size: 13px !important;
            font-weight: bold !important;
            background: #0d9488 !important;
            color: #ffffff !important;
            border-radius: 4px !important;
            padding: 5px 6px !important;
        }

        .a4-thanks {
            text-align: center !important;
            margin-top: 18px !important;
            font-size: 11.5px !important;
            color: #0d9488 !important;
            font-weight: bold !important;
        }

        .a4-footer {
            margin-top: 18px !important;
            font-size: 9.5px !important;
            color: #6b7280 !important;
            display: flex !important;
            justify-content: space-between !important;
        }

        .a4-note {
            font-size: 9px !important;
            color: #9ca3af !important;
            margin-top: 6px !important;
        }

        .kasir-modal-footer {
            padding: 0.85rem 1.25rem !important;
            border-top: 1px solid rgba(226, 232, 240, 0.8) !important;
            background: rgba(248, 250, 252, 0.9) !important;
        }

        .dark .kasir-modal-footer,
        html.dark .kasir-modal-footer {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(51, 65, 85, 0.6) !important;
        }
    </style>

    <div class="kasir-pos">
        <div class="kasir-steps" aria-label="Alur kasir">
            <div class="kasir-step is-active">
                <span class="kasir-step-num">1</span>
                Pilih produk
            </div>
            <div class="kasir-step {{ ! empty($this->cart) ? 'is-active' : '' }}">
                <span class="kasir-step-num">2</span>
                Keranjang
            </div>
            <div class="kasir-step {{ ! empty($this->cart) ? 'is-active' : '' }}">
                <span class="kasir-step-num">3</span>
                Pembayaran
            </div>
        </div>

        <div class="kasir-layout">
            <div class="kasir-panel">
                <x-filament::section icon="heroicon-o-shopping-bag">
                    <x-slot name="heading">
                        Langkah 1 — Pilih Produk
                    </x-slot>
                    <x-slot name="description">
                        Cari produk, lalu tekan tombol Tambah untuk memasukkan ke keranjang.
                    </x-slot>

                    <div class="kasir-search">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Cari nama produk..."
                                autocomplete="off"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    @if ($this->products()->isEmpty())
                        <p class="kasir-empty">
                            @if ($this->search !== '')
                                Tidak ada produk yang cocok dengan "{{ $this->search }}".
                            @else
                                Belum ada produk aktif. Tambahkan produk terlebih dahulu di menu Produk.
                            @endif
                        </p>
                    @else
                        <div class="kasir-product-grid" style="margin-top: 1rem;">
                            @foreach ($this->products() as $product)
                                @php
                                    $isOutOfStock = $product->track_stock && (float) $product->stock <= 0;
                                @endphp
                                <button
                                    type="button"
                                    class="kasir-product {{ $isOutOfStock ? 'is-out-of-stock' : '' }}"
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart({{ $product->id }})"
                                    title="{{ $product->name }}"
                                    @if ($isOutOfStock) disabled @endif
                                >
                                    <div class="kasir-product-top">
                                        <div class="kasir-product-info">
                                            <span class="kasir-product-name">{{ $product->name }}</span>
                                            @if (! empty($product->description))
                                                <span class="kasir-product-desc">{{ $product->description }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="kasir-product-bottom">
                                        <div class="kasir-product-meta-bottom">
                                            <span class="kasir-product-price">{{ rupiah($product->price) }}</span>
                                            @if ($product->track_stock)
                                                <span class="kasir-product-stock-badge {{ (float)$product->stock <= 0 ? 'is-danger' : ((float)$product->stock <= 5 ? 'is-warning' : 'is-success') }}">
                                                    {{ $product->stock_label }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="kasir-product-add-btn">
                                            @if(! $isOutOfStock)
                                                <svg class="kasir-add-icon" viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px; display: inline-block; margin-right: 2px;"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                                            @endif
                                            {{ $isOutOfStock ? 'Habis' : 'Tambah' }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            </div>

            <div class="kasir-panel kasir-sticky">
                <x-filament::section icon="heroicon-o-shopping-cart">
                    <x-slot name="heading">
                        Langkah 2 — Keranjang
                    </x-slot>
                    <x-slot name="description">
                        @if (empty($this->cart))
                            Belum ada item. Pilih produk di sebelah kiri.
                        @else
                            {{ count($this->cart) }} jenis produk · Total qty {{ (float) collect($this->cart)->sum('quantity') == (int) collect($this->cart)->sum('quantity') ? (int) collect($this->cart)->sum('quantity') : collect($this->cart)->sum('quantity') }}
                        @endif
                    </x-slot>

                    @if (empty($this->cart))
                        <p class="kasir-empty">Keranjang masih kosong.</p>
                    @else
                        <div class="kasir-cart-list">
                            @foreach ($this->cart as $index => $row)
                                <div class="kasir-cart-row" wire:key="cart-item-{{ $row['product_id'] }}">
                                    <div class="kasir-cart-top">
                                        <div class="kasir-cart-info">
                                            <span class="kasir-cart-name">{{ $row['name'] }}</span>
                                            <div class="kasir-price-edit">
                                                <span class="kasir-price-label">Rp</span>
                                                <input
                                                    type="number"
                                                    class="kasir-price-input"
                                                    min="0"
                                                    step="any"
                                                    inputmode="decimal"
                                                    value="{{ $row['price'] }}"
                                                    wire:change="setPrice({{ $index }}, $event.target.value)"
                                                    x-on:keydown.enter.prevent="$wire.setPrice({{ $index }}, $event.target.value); $event.target.blur()"
                                                    aria-label="Ubah harga {{ $row['name'] }}"
                                                    title="Klik untuk mengubah harga satuan item ini"
                                                />
                                                <span class="kasir-price-unit">/ item</span>
                                                @if (isset($row['original_price']) && (float) $row['price'] !== (float) $row['original_price'])
                                                    <span class="kasir-price-badge" title="Harga standar {{ rupiah($row['original_price']) }}">Diubah</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="kasir-cart-subtotal">{{ rupiah($row['subtotal']) }}</div>
                                    </div>

                                    <div class="kasir-cart-bottom">
                                        <div class="kasir-qty" role="group" aria-label="Jumlah {{ $row['name'] }}">
                                            <button
                                                type="button"
                                                class="kasir-qty-btn"
                                                wire:click="decrementQty({{ $index }})"
                                                aria-label="Kurangi"
                                            >−</button>
                                            <input
                                                type="number"
                                                class="kasir-qty-input"
                                                min="0.01"
                                                step="any"
                                                inputmode="decimal"
                                                value="{{ $row['quantity'] }}"
                                                wire:change="setQty({{ $index }}, $event.target.value)"
                                                x-on:keydown.enter.prevent="$wire.setQty({{ $index }}, $event.target.value); $event.target.blur()"
                                                aria-label="Ketik jumlah {{ $row['name'] }}"
                                            />
                                            <button
                                                type="button"
                                                class="kasir-qty-btn"
                                                wire:click="incrementQty({{ $index }})"
                                                aria-label="Tambah"
                                            >+</button>
                                        </div>

                                        <x-filament::button
                                            type="button"
                                            color="danger"
                                            size="xs"
                                            outlined
                                            icon="heroicon-o-trash"
                                            wire:click="removeFromCart({{ $index }})"
                                            class="kasir-remove-btn"
                                        >
                                            Hapus
                                        </x-filament::button>
                                    </div>

                                    <div class="kasir-cart-notes" style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed rgba(156, 163, 175, 0.3);">
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px; color: #6b7280; flex-shrink: 0;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                            <input
                                                type="text"
                                                class="kasir-notes-input"
                                                placeholder="Keterangan / rincian roll (cth: kain 1 53m, kain 2 54m total 107m 2 roll)..."
                                                value="{{ $row['notes'] ?? '' }}"
                                                wire:change="setNotes({{ $index }}, $event.target.value)"
                                                x-on:keydown.enter.prevent="$wire.setNotes({{ $index }}, $event.target.value); $event.target.blur()"
                                                style="width: 100%; font-size: 0.76rem; padding: 3px 6px; border: 1px solid rgba(156, 163, 175, 0.4); border-radius: 4px; background: rgba(0, 0, 0, 0.02); color: inherit;"
                                                title="Keterangan tambahan untuk dicetak di nota (misal: rincian roll / meteran)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="kasir-total">
                            <span class="kasir-total-label">Total Belanja</span>
                            <span class="kasir-total-value">{{ rupiah($this->cartTotal()) }}</span>
                        </div>
                    @endif
                </x-filament::section>

                <x-filament::section icon="heroicon-o-banknotes">
                    <x-slot name="heading">
                        Langkah 3 — Pembayaran
                    </x-slot>
                    <x-slot name="description">
                        Pilih Tunai, Transfer, atau Kredit, lalu proses penjualan.
                    </x-slot>

                    <div class="kasir-pay-methods" role="group" aria-label="Metode pembayaran">
                        <button
                            type="button"
                            class="kasir-pay-btn {{ $this->paymentMethod === Sale::PAYMENT_METHOD_CASH ? 'is-selected' : '' }}"
                            data-method="cash"
                            wire:click="$set('paymentMethod', '{{ Sale::PAYMENT_METHOD_CASH }}')"
                        >
                            <strong>Tunai</strong>
                            <span>Bayar langsung di kasir</span>
                        </button>
                        <button
                            type="button"
                            class="kasir-pay-btn {{ $this->paymentMethod === Sale::PAYMENT_METHOD_TRANSFER ? 'is-selected' : '' }}"
                            data-method="transfer"
                            wire:click="$set('paymentMethod', '{{ Sale::PAYMENT_METHOD_TRANSFER }}')"
                        >
                            <strong>Transfer</strong>
                            <span>Bayar via transfer bank</span>
                        </button>
                        <button
                            type="button"
                            class="kasir-pay-btn {{ $this->paymentMethod === Sale::PAYMENT_METHOD_SPLIT ? 'is-selected' : '' }}"
                            data-method="split"
                            wire:click="$set('paymentMethod', '{{ Sale::PAYMENT_METHOD_SPLIT }}')"
                        >
                            <strong>Tunai + Transfer</strong>
                            <span>Sebagian tunai & transfer</span>
                        </button>
                        <button
                            type="button"
                            class="kasir-pay-btn {{ $this->paymentMethod === Sale::PAYMENT_METHOD_RECEIVABLE ? 'is-selected' : '' }}"
                            data-method="receivable"
                            wire:click="$set('paymentMethod', '{{ Sale::PAYMENT_METHOD_RECEIVABLE }}')"
                        >
                            <strong>Kredit</strong>
                            <span>Catat sebagai piutang</span>
                        </button>
                    </div>

                    <div class="kasir-field" x-data="{ isOpen: false }" x-on:click.outside="isOpen = false">
                        <label class="kasir-label" for="kasir-receivable-party">Pilih Pelanggan</label>
                        
                        <div style="position: relative; width: 100%;">
                            <x-filament::input.wrapper>
                                @if($receivablePartyId)
                                    <x-slot name="prefix">
                                        <span class="inline-flex items-center gap-1 rounded bg-primary-500/10 px-1.5 py-0.5 text-xs font-medium text-primary-600 dark:text-primary-400">
                                            Dipilih
                                        </span>
                                    </x-slot>
                                @endif

                                <x-filament::input
                                    type="search"
                                    wire:model.live.debounce.250ms="partySearch"
                                    placeholder="Cari & pilih nama / telepon..."
                                    autocomplete="off"
                                    x-on:focus="isOpen = true"
                                    x-on:click="isOpen = true"
                                    x-on:keydown.enter.prevent=""
                                />

                                @if($receivablePartyId || $partySearch !== '')
                                    <x-slot name="suffix">
                                        <button
                                            type="button"
                                            wire:click="clearSelectedParty"
                                            x-on:click="isOpen = false"
                                            style="display: inline-flex; align-items: center; justify-content: center; height: 100%; border: 0; background: transparent; cursor: pointer; color: var(--kasir-muted);"
                                            class="hover:text-gray-900 dark:hover:text-white"
                                        >
                                            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                                        </button>
                                    </x-slot>
                                @endif
                            </x-filament::input.wrapper>

                            <input type="hidden" id="kasir-receivable-party" wire:model="receivablePartyId" />

                            <div
                                x-show="isOpen"
                                x-transition
                                class="kasir-autocomplete-dropdown"
                            >
                                @if($this->parties()->isEmpty())
                                    <div style="padding: 12px; font-size: 0.875rem; color: var(--kasir-muted); text-align: center;">
                                        Tidak ada pelanggan yang cocok.
                                    </div>
                                @else
                                    @foreach ($this->parties() as $party)
                                        <button
                                            type="button"
                                            wire:click="selectParty({{ $party->id }})"
                                            x-on:click="isOpen = false"
                                            class="kasir-autocomplete-item"
                                        >
                                            <span class="kasir-autocomplete-name">{{ $party->name }}</span>
                                            @if($party->phone || $party->address)
                                                <span class="kasir-autocomplete-meta">
                                                    {{ $party->phone ? $party->phone : '' }} {{ $party->phone && $party->address ? ' · ' : '' }} {{ $party->address ? Str::limit($party->address, 40) : '' }}
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        @if ($this->paymentMethod === Sale::PAYMENT_METHOD_RECEIVABLE)
                            <p class="kasir-hint">
                                Penjualan kredit otomatis tercatat sebagai nota piutang di menu Manajemen Piutang.
                            </p>
                        @endif
                    </div>

                    @if ($this->paymentMethod === Sale::PAYMENT_METHOD_CASH)
                        <div
                            class="kasir-field"
                            wire:key="payment-cash-section"
                            x-data="{
                                received: $wire.entangle('receivedAmount'),
                                parseNum(val) {
                                    if (val === null || val === undefined || val === '') return 0;
                                    if (typeof val === 'number') return isNaN(val) ? 0 : val;
                                    let str = String(val).trim();
                                    if (!str) return 0;
                                    if (str.includes('.') && !str.includes(',')) {
                                        let parts = str.split('.');
                                        if (parts.length > 1 && parts.slice(1).every(p => p.length === 3)) str = parts.join('');
                                    } else if (str.includes(',') && !str.includes('.')) {
                                        let parts = str.split(',');
                                        if (parts.length > 1 && parts.slice(1).every(p => p.length === 3)) str = parts.join('');
                                        else str = str.replace(',', '.');
                                    } else if (str.includes('.') && str.includes(',')) {
                                        str = str.replace(/\./g, '').replace(',', '.');
                                    }
                                    let num = parseFloat(str);
                                    return isNaN(num) ? 0 : num;
                                },
                                get currentTotal() {
                                    return ($wire.cart || []).reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
                                },
                                get change() {
                                    let r = this.parseNum(this.received);
                                    return Math.max(0, r - this.currentTotal);
                                }
                            }"
                        >
                            <label class="kasir-label" for="kasir-received-amount">Uang Diterima</label>
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    id="kasir-received-amount"
                                    type="number"
                                    x-model="received"
                                    wire:model.blur="receivedAmount"
                                    min="0"
                                    step="any"
                                    placeholder="contoh: 100000 atau 1.24"
                                    inputmode="decimal"
                                />
                            </x-filament::input.wrapper>

                            <div class="kasir-change" x-bind:class="change > 0 ? 'is-positive' : ''">
                                <span class="kasir-change-label">Kembalian</span>
                                <span class="kasir-change-value" x-text="'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(change)"></span>
                            </div>
                        </div>
                    @endif

                    @if ($this->paymentMethod === Sale::PAYMENT_METHOD_SPLIT)
                        <div
                            class="kasir-field"
                            wire:key="payment-split-section"
                            x-data="{
                                cash: $wire.entangle('cashAmount'),
                                transfer: $wire.entangle('transferAmount'),
                                parseNum(val) {
                                    if (val === null || val === undefined || val === '') return 0;
                                    if (typeof val === 'number') return isNaN(val) ? 0 : val;
                                    let str = String(val).trim();
                                    if (!str) return 0;
                                    if (str.includes('.') && !str.includes(',')) {
                                        let parts = str.split('.');
                                        if (parts.length > 1 && parts.slice(1).every(p => p.length === 3)) str = parts.join('');
                                    } else if (str.includes(',') && !str.includes('.')) {
                                        let parts = str.split(',');
                                        if (parts.length > 1 && parts.slice(1).every(p => p.length === 3)) str = parts.join('');
                                        else str = str.replace(',', '.');
                                    } else if (str.includes('.') && str.includes(',')) {
                                        str = str.replace(/\./g, '').replace(',', '.');
                                    }
                                    let num = parseFloat(str);
                                    return isNaN(num) ? 0 : num;
                                },
                                get currentTotal() {
                                    return ($wire.cart || []).reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
                                },
                                get totalReceived() {
                                    let c = this.parseNum(this.cash);
                                    let t = this.parseNum(this.transfer);
                                    return c + t;
                                },
                                get change() {
                                    return Math.max(0, this.totalReceived - this.currentTotal);
                                }
                            }"
                        >
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                                <div>
                                    <label class="kasir-label" for="kasir-cash-amount">Nominal Tunai</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            id="kasir-cash-amount"
                                            type="number"
                                            x-model="cash"
                                            wire:model.blur="cashAmount"
                                            min="0"
                                            step="any"
                                            placeholder="contoh: 50000 atau 1.24"
                                            inputmode="decimal"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="kasir-label" for="kasir-transfer-amount">Nominal Transfer</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            id="kasir-transfer-amount"
                                            type="number"
                                            x-model="transfer"
                                            wire:model.blur="transferAmount"
                                            min="0"
                                            step="any"
                                            placeholder="contoh: 50000 atau 1.24"
                                            inputmode="decimal"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                            </div>

                            <div class="kasir-change" x-bind:class="change > 0 ? 'is-positive' : ''" style="margin-top: 0.75rem;">
                                <div style="display: flex; flex-direction: column;">
                                    <span class="kasir-change-label">Total Dibayar</span>
                                    <span class="kasir-change-value" x-text="'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(totalReceived)"></span>
                                </div>
                                <div style="text-align: right; display: flex; flex-direction: column;">
                                    <span class="kasir-change-label">Kembalian</span>
                                    <span class="kasir-change-value" x-text="'Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(change)"></span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="kasir-actions">
                        <x-filament::button
                            type="button"
                            color="primary"
                            size="xl"
                            icon="heroicon-o-check-circle"
                            wire:click="processSale"
                            class="kasir-process-btn"
                        >
                            Proses Penjualan
                        </x-filament::button>

                        @if (! empty($this->cart))
                            <x-filament::button
                                type="button"
                                color="info"
                                size="lg"
                                icon="heroicon-o-document-magnifying-glass"
                                wire:click="openPreviewModal"
                            >
                                Preview Nota
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                color="gray"
                                size="lg"
                                icon="heroicon-o-x-mark"
                                wire:click="clearCart"
                            >
                                Bersihkan
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::section>
            </div>
        </div>

        @if ($this->result)
            <x-filament::section icon="heroicon-o-check-circle">
                <x-slot name="heading">
                    Penjualan Berhasil
                </x-slot>
                <x-slot name="description">
                    Transaksi {{ $this->result['transaction_number'] }}
                </x-slot>

                <div class="kasir-result-grid">
                    <div class="kasir-stat">
                        <div class="kasir-stat-label">Total Belanja</div>
                        <div class="kasir-stat-value">{{ rupiah($this->result['total']) }}</div>
                    </div>
                    <div class="kasir-stat">
                        <div class="kasir-stat-label">Metode Pembayaran</div>
                        <div class="kasir-stat-value">{{ $this->result['payment_method_label'] }}</div>
                    </div>
                    @if ($this->result['party_name'])
                        <div class="kasir-stat">
                            <div class="kasir-stat-label">Pelanggan</div>
                            <div class="kasir-stat-value">{{ $this->result['party_name'] }}</div>
                        </div>
                    @endif
                    @if ($this->result['payment_method'] === Sale::PAYMENT_METHOD_CASH)
                        <div class="kasir-stat">
                            <div class="kasir-stat-label">Kembalian</div>
                            <div class="kasir-stat-value">{{ rupiah($this->result['change']) }}</div>
                        </div>
                    @elseif ($this->result['payment_method'] === Sale::PAYMENT_METHOD_SPLIT)
                        <div class="kasir-stat">
                            <div class="kasir-stat-label">Rincian Bayar</div>
                            <div class="kasir-stat-value" style="font-size: 0.875rem;">
                                Tunai: {{ rupiah($this->result['cash_amount']) }}<br>
                                Transfer: {{ rupiah($this->result['transfer_amount']) }}
                            </div>
                        </div>
                        <div class="kasir-stat">
                            <div class="kasir-stat-label">Kembalian</div>
                            <div class="kasir-stat-value">{{ rupiah($this->result['change']) }}</div>
                        </div>
                    @endif
                    <div class="kasir-stat">
                        <div class="kasir-stat-label">Jumlah Item</div>
                        <div class="kasir-stat-value">{{ $this->result['items_count'] }}</div>
                    </div>
                </div>

                <div class="kasir-actions" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    {{-- Layout 3 (Sekarang): Continuous Ringkas 1-Baris --}}
                    <x-filament::button
                        tag="a"
                        href="{{ url('/sales/' . $this->result['sale_id'] . '/thermal?layout=compact') }}"
                        target="_blank"
                        color="success"
                        size="lg"
                        icon="heroicon-o-printer"
                    >
                        Continuous Ringkas (1-Baris)
                    </x-filament::button>

                    {{-- Layout 2: Continuous Detail 2-Baris --}}
                    <x-filament::button
                        tag="a"
                        href="{{ url('/sales/' . $this->result['sale_id'] . '/thermal?layout=detail') }}"
                        target="_blank"
                        color="warning"
                        size="lg"
                        icon="heroicon-o-document-text"
                    >
                        Continuous Detail (2-Baris)
                    </x-filament::button>

                    {{-- Layout 1: Thermal Roll 72mm --}}
                    <x-filament::button
                        tag="a"
                        href="{{ url('/sales/' . $this->result['sale_id'] . '/thermal?layout=roll') }}"
                        target="_blank"
                        color="gray"
                        size="lg"
                        icon="heroicon-o-receipt-percent"
                    >
                        Thermal Roll (72mm)
                    </x-filament::button>

                    {{-- Nota A4 --}}
                    <x-filament::button
                        tag="a"
                        href="{{ url('/sales/' . $this->result['sale_id'] . '/nota') }}"
                        target="_blank"
                        color="info"
                        size="lg"
                        icon="heroicon-o-document-text"
                    >
                        Nota A4
                    </x-filament::button>
                    @if (!empty($this->result['wa_link']))
                        <x-filament::button
                            tag="a"
                            href="{{ $this->result['wa_link'] }}"
                            target="_blank"
                            color="warning"
                            size="lg"
                            icon="heroicon-o-chat-bubble-left-right"
                        >
                            Kirim WA
                        </x-filament::button>
                    @else
                        <x-filament::button
                            type="button"
                            color="gray"
                            size="lg"
                            icon="heroicon-o-chat-bubble-left-right"
                            disabled
                            x-tooltip="'Nomor HP pelanggan belum diisi'"
                        >
                            Kirim WA
                        </x-filament::button>
                    @endif
                    <x-filament::button
                        type="button"
                        color="gray"
                        size="lg"
                        wire:click="$set('result', null)"
                    >
                        Transaksi Baru
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        {{-- MODAL PREVIEW NOTA SEBELUM TRANSAKSI DIBUAT --}}
        @if ($this->showPreviewModal && ! empty($this->cart))
            <div
                class="kasir-modal-backdrop"
                wire:keydown.escape="closePreviewModal"
                tabindex="-1"
            >
                <div
                    class="kasir-modal-container"
                    x-data="{
                        doPrint() {
                            const printElem = document.getElementById('kasir-receipt-paper-printable');
                            if (!printElem) {
                                alert('Konten nota tidak ditemukan.');
                                return;
                            }

                            let printFrame = document.getElementById('kasir-print-iframe');
                            if (!printFrame) {
                                printFrame = document.createElement('iframe');
                                printFrame.id = 'kasir-print-iframe';
                                printFrame.style.position = 'fixed';
                                printFrame.style.right = '0';
                                printFrame.style.bottom = '0';
                                printFrame.style.width = '0';
                                printFrame.style.height = '0';
                                printFrame.style.border = '0';
                                document.body.appendChild(printFrame);
                            }

                            const doc = printFrame.contentWindow || printFrame.contentDocument;
                            const frameDoc = doc.document || doc;
                            frameDoc.open();
                            frameDoc.write(`<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='utf-8'>
    <title>Nota Penjualan (Draft) - Toko Kain Bu Ulil</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page {
            margin: 12mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            padding: 10px;
        }
        .a4-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 14px; }
        .a4-brand h1 { margin: 0; font-size: 18px; color: #0d9488; font-weight: bold; }
        .a4-brand p { margin: 2px 0 0; font-size: 11px; color: #6b7280; }
        .a4-nota-no { text-align: right; }
        .a4-nota-no .label { font-size: 9px; text-transform: uppercase; color: #6b7280; font-weight: 600; }
        .a4-nota-no .value { font-size: 16px; font-weight: bold; color: #111827; }

        .a4-meta { margin-bottom: 12px; font-size: 11px; }
        .a4-meta table { width: 100%; border-collapse: collapse; }
        .a4-meta td { padding: 2.5px 0; vertical-align: top; }
        .a4-meta .k { color: #6b7280; width: 130px; }

        table.a4-items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.a4-items thead th {
            background: #0d9488;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            font-size: 9.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        table.a4-items tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        table.a4-items tbody tr:nth-child(even) { background: #f9fafb; }

        .a4-total-box { margin-left: auto; width: 270px; }
        .a4-total-box table { width: 100%; border-collapse: collapse; }
        .a4-total-box td { padding: 4px 6px; font-size: 11px; }
        .a4-total-box .grand td { font-size: 13px; font-weight: bold; background: #0d9488; color: #fff; border-radius: 4px; padding: 5px 6px; }

        .a4-footer { margin-top: 24px; font-size: 10px; color: #6b7280; display: flex; justify-content: space-between; }
        .a4-thanks { text-align: center; margin-top: 18px; font-size: 12px; color: #0d9488; font-weight: bold; }
        .a4-note { font-size: 9px; color: #9ca3af; margin-top: 8px; }
    </style>
</head>
<body>
    \${printElem.innerHTML}
</body>
</html>`);
                            frameDoc.close();

                            setTimeout(() => {
                                try {
                                    printFrame.contentWindow.focus();
                                    printFrame.contentWindow.print();
                                } catch (e) {
                                    console.error('Print iframe failed, fallback to window.print', e);
                                    window.print();
                                }
                            }, 250);
                        }
                    }"
                    x-on:click.outside="$wire.closePreviewModal()"
                >
                    {{-- Modal Header --}}
                    <div class="kasir-modal-header">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div class="kasir-modal-icon-badge">
                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="kasir-modal-title">Pratinjau Nota Penjualan</h3>
                                <span class="kasir-modal-subtitle">Periksa rincian sebelum nota dibuat</span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="kasir-draft-badge">DRAFT</span>
                            <button
                                type="button"
                                wire:click="closePreviewModal"
                                class="kasir-modal-close-btn"
                                title="Tutup (Esc)"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body: Authentic A4 Document Simulation --}}
                    <div class="kasir-modal-body">
                        <div class="kasir-a4-paper" id="kasir-receipt-paper-printable">
                            {{-- Header --}}
                            <div class="a4-header">
                                <div class="a4-brand">
                                    <h1>Toko Kain Bu Ulil</h1>
                                    <p>Nota Penjualan</p>
                                </div>
                                <div class="a4-nota-no">
                                    <div class="label">No. Transaksi</div>
                                    <div class="value">[DRAFT]</div>
                                </div>
                            </div>

                            {{-- Meta Transaksi --}}
                            <div class="a4-meta">
                                <table>
                                    <tr>
                                        <td class="k">Tanggal</td>
                                        <td>: {{ $this->saleDate ? date('d M Y', strtotime($this->saleDate)) : date('d M Y') }} ({{ date('H:i') }})</td>
                                    </tr>
                                    @php
                                        $selectedParty = $this->getSelectedParty();
                                    @endphp
                                    @if ($selectedParty)
                                        <tr>
                                            <td class="k">Pelanggan</td>
                                            <td>: {{ $selectedParty->name }} {{ $selectedParty->phone ? '('.$selectedParty->phone.')' : '' }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="k">Pelanggan</td>
                                            <td>: Pelanggan Umum</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="k">Metode Pembayaran</td>
                                        <td>: {{ $this->getPaymentMethodLabel() }}</td>
                                    </tr>
                                    @if($this->paymentMethod === Sale::PAYMENT_METHOD_RECEIVABLE)
                                        <tr>
                                            <td class="k">Nota Piutang</td>
                                            <td>: [Otomatis dicatat ke Piutang Pelanggan]</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="k">Kasir</td>
                                        <td>: {{ auth()->user()?->name ?: '-' }}</td>
                                    </tr>
                                </table>
                            </div>

                            {{-- Tabel Daftar Item --}}
                            <table class="a4-items">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 44%;">Produk</th>
                                        <th style="width: 17%; text-align: right;">Harga</th>
                                        <th style="width: 14%; text-align: right;">Jumlah</th>
                                        <th style="width: 20%; text-align: right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->cart as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div style="font-weight: 600;">{{ $item['name'] }}</div>
                                                @if (! empty($item['notes']))
                                                    <div style="font-size: 9.5px; color: #4b5563; font-weight: normal; margin-top: 2px;">
                                                        * {{ $item['notes'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="text-align: right;">{{ number_format((float)$item['price'], 0, ',', '.') }}</td>
                                            <td style="text-align: right;">{{ (float)$item['quantity'] == (int)$item['quantity'] ? (int)$item['quantity'] : $item['quantity'] }}</td>
                                            <td style="text-align: right;">{{ number_format((float)$item['subtotal'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Bagian Total --}}
                            <div class="a4-total-box">
                                <table>
                                    <tr>
                                        <td>Total Belanja</td>
                                        <td style="text-align: right;">{{ number_format($this->cartTotal(), 0, ',', '.') }}</td>
                                    </tr>
                                    @if ($this->paymentMethod === Sale::PAYMENT_METHOD_CASH)
                                        @php
                                            $received = static::parseNumericAmount($this->receivedAmount) ?? 0.0;
                                            $change = $this->changeAmount();
                                        @endphp
                                        <tr>
                                            <td>Uang Diterima</td>
                                            <td style="text-align: right;">{{ number_format($received, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Kembalian</td>
                                            <td style="text-align: right;">{{ number_format($change, 0, ',', '.') }}</td>
                                        </tr>
                                    @elseif ($this->paymentMethod === Sale::PAYMENT_METHOD_SPLIT)
                                        @php
                                            $cash = static::parseNumericAmount($this->cashAmount) ?? 0.0;
                                            $transfer = static::parseNumericAmount($this->transferAmount) ?? 0.0;
                                            $change = $this->changeAmount();
                                        @endphp
                                        <tr>
                                            <td>Bayar Tunai</td>
                                            <td style="text-align: right;">{{ number_format($cash, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Bayar Transfer</td>
                                            <td style="text-align: right;">{{ number_format($transfer, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Total Dibayar</td>
                                            <td style="text-align: right;">{{ number_format($cash + $transfer, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Kembalian</td>
                                            <td style="text-align: right;">{{ number_format($change, 0, ',', '.') }}</td>
                                        </tr>
                                    @elseif ($this->paymentMethod === Sale::PAYMENT_METHOD_TRANSFER)
                                        <tr>
                                            <td>Status</td>
                                            <td style="text-align: right;">Transfer (LUNAS)</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td>Status</td>
                                            <td style="text-align: right;">Kredit (Piutang)</td>
                                        </tr>
                                    @endif
                                    <tr class="grand">
                                        <td>TOTAL</td>
                                        <td style="text-align: right;">{{ number_format($this->cartTotal(), 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="a4-thanks">Terima kasih telah berbelanja di Toko Kain Bu Ulil</div>

                            <div class="a4-footer">
                                <span>Dicetak pada: {{ date('d M Y H:i') }}</span>
                                <span>Oleh: {{ auth()->user()?->name ?: 'Kasir' }}</span>
                            </div>
                            <div class="a4-note">
                                * Nota ini adalah pratinjau (draft) sebelum transaksi resmi disimpan. Jumlah tercantum dalam Rupiah.
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="kasir-modal-footer">
                        <div style="display: flex; gap: 0.5rem; width: 100%; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                            <x-filament::button
                                type="button"
                                color="gray"
                                size="md"
                                icon="heroicon-o-arrow-left"
                                wire:click="closePreviewModal"
                            >
                                Tutup & Ubah
                            </x-filament::button>

                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <button
                                    type="button"
                                    id="btn-cetak-draft-a4"
                                    x-on:click="doPrint()"
                                    style="background-color: #0284c7; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; border: none; transition: background 0.15s ease;"
                                    onmouseover="this.style.backgroundColor='#0369a1'"
                                    onmouseout="this.style.backgroundColor='#0284c7'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.15rem; height: 1.15rem;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                    </svg>
                                    <span>Cetak Draft A4</span>
                                </button>

                                <x-filament::button
                                    type="button"
                                    color="primary"
                                    size="md"
                                    icon="heroicon-o-check-circle"
                                    wire:click="processSale"
                                    wire:loading.attr="disabled"
                                >
                                    Proses Penjualan
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
