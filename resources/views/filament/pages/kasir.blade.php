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
    </div>
</x-filament-panels::page>
