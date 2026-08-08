@php
    use App\Models\Sale;
@endphp

<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/kasir.css') }}?v=2" data-navigate-track />

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
                                <button
                                    type="button"
                                    class="kasir-product"
                                    wire:click="addToCart({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart({{ $product->id }})"
                                >
                                    <span class="kasir-product-meta">
                                        <span class="kasir-product-name">{{ $product->name }}</span>
                                        <span class="kasir-product-price">{{ rupiah($product->price) }}</span>
                                    </span>
                                    <span class="kasir-product-add">Tambah</span>
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
                            {{ count($this->cart) }} jenis produk · Total qty {{ collect($this->cart)->sum('quantity') }}
                        @endif
                    </x-slot>

                    @if (empty($this->cart))
                        <p class="kasir-empty">Keranjang masih kosong.</p>
                    @else
                        <div class="kasir-cart-list">
                            @foreach ($this->cart as $index => $row)
                                <div class="kasir-cart-row" wire:key="cart-{{ $row['product_id'] }}-{{ $index }}-{{ $row['quantity'] }}">
                                    <div class="kasir-cart-top">
                                        <div class="kasir-cart-info">
                                            <span class="kasir-cart-name">{{ $row['name'] }}</span>
                                            <span class="kasir-cart-unit">{{ rupiah($row['price']) }} / item</span>
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
                                                min="1"
                                                step="1"
                                                inputmode="numeric"
                                                x-data="{ qty: @js($row['quantity']) }"
                                                x-bind:value="qty"
                                                x-on:input="qty = $event.target.value"
                                                x-on:change="$wire.setQty({{ $index }}, qty)"
                                                x-on:blur="$wire.setQty({{ $index }}, qty)"
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
                            x-data="{
                                received: @js($this->receivedAmount ?? ''),
                                total: @js($this->cartTotal()),
                                get change() {
                                    let r = parseFloat(this.received) || 0;
                                    return Math.max(0, r - this.total);
                                }
                            }"
                        >
                            <label class="kasir-label" for="kasir-received-amount">Uang Diterima</label>
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    id="kasir-received-amount"
                                    type="number"
                                    x-model="received"
                                    x-on:blur="$wire.set('receivedAmount', parseFloat(received) || null)"
                                    min="0"
                                    step="1000"
                                    placeholder="contoh: 100000"
                                    inputmode="numeric"
                                />
                            </x-filament::input.wrapper>

                            <div class="kasir-change" x-bind:class="change > 0 ? 'is-positive' : ''">
                                <span class="kasir-change-label">Kembalian</span>
                                <span class="kasir-change-value" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(change)"></span>
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
                    @endif
                    <div class="kasir-stat">
                        <div class="kasir-stat-label">Jumlah Item</div>
                        <div class="kasir-stat-value">{{ $this->result['items_count'] }}</div>
                    </div>
                </div>

                <div class="kasir-actions">
                    <x-filament::button
                        type="button"
                        color="success"
                        size="lg"
                        icon="heroicon-o-printer"
                        wire:click="printThermal"
                    >
                        Cetak Struk Thermal
                    </x-filament::button>
                    <x-filament::button
                        type="button"
                        color="info"
                        size="lg"
                        icon="heroicon-o-document-text"
                        wire:click="printNota"
                    >
                        Cetak Nota A4
                    </x-filament::button>
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
