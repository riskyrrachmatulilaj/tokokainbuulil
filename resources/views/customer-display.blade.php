<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#090d16">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Customer Display - Toko Kain Bu Ulil</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                        },
                        teal: {
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b1120;
            color: #f8fafc;
            overflow-x: hidden;
            touch-action: manipulation;
            user-select: none;
        }

        /* Ambient Glow Effect */
        .glow-emerald {
            text-shadow: 0 0 25px rgba(52, 211, 153, 0.45);
        }
        .glow-amber {
            text-shadow: 0 0 25px rgba(251, 191, 36, 0.45);
        }
        .glow-box {
            box-shadow: 0 0 40px -10px rgba(13, 148, 136, 0.35);
        }

        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(51, 65, 85, 0.8);
            border-radius: 9999px;
        }

        /* Animations */
        @keyframes pulseSlow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.02); }
        }
        .animate-pulse-slow {
            animation: pulseSlow 3s ease-in-out infinite;
        }

        @keyframes itemPop {
            0% { opacity: 0; transform: translateY(-8px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .item-row-new {
            animation: itemPop 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">
    <!-- Main Display Container -->
    <div id="app" class="flex-1 flex flex-col w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <!-- Header Bar -->
        <header class="flex items-center justify-between pb-4 sm:pb-6 border-b border-slate-800/80">
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-400 flex items-center justify-center shadow-lg shadow-teal-500/20 text-white font-black text-lg sm:text-xl">
                    BU
                </div>
                <div>
                    <h1 class="text-lg sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                        TOKO KAIN BU ULIL
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 font-medium">Layar Tampilan Pelanggan</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Live Clock -->
                <div class="hidden sm:flex flex-col items-end">
                    <span id="liveTime" class="font-mono text-base font-bold text-slate-200">--:--:--</span>
                    <span id="liveDate" class="text-xs text-slate-400 font-medium">-- --- ----</span>
                </div>

                <!-- Status Connection Badge -->
                <div id="connectionBadge" class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span id="connectionText">Terhubung</span>
                </div>

                <!-- Fullscreen Toggle Button -->
                <button onclick="toggleFullscreen()" class="p-2 rounded-xl bg-slate-800/70 border border-slate-700/60 text-slate-300 hover:text-white hover:bg-slate-700 transition" title="Layar Penuh">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Main Body: Dynamically switched by JS -->
        <main class="flex-1 flex flex-col justify-center py-4 sm:py-6">
            
            <!-- 1. VIEW IDLE (Keranjang Kosong) -->
            <div id="viewIdle" class="flex flex-col items-center justify-center text-center py-12 sm:py-16">
                <div class="relative mb-6">
                    <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-3xl bg-gradient-to-tr from-teal-500/20 to-emerald-500/20 border border-teal-500/30 flex items-center justify-center text-teal-400 shadow-2xl shadow-teal-500/10 animate-pulse-slow">
                        <svg class="w-14 h-14 sm:w-18 sm:h-18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mb-3">
                    Selamat Datang di <span class="bg-gradient-to-r from-teal-400 to-emerald-400 bg-clip-text text-transparent">Toko Kain Bu Ulil</span>
                </h2>
                <p class="text-base sm:text-xl text-slate-300 max-w-2xl font-normal leading-relaxed mb-6">
                    Pusat grosir & eceran aneka kain kasur, bantal, guling, sprei, bahan boneka, busa, serta berbagai perlengkapan kain berkualitas dengan harga terbaik.
                </p>

                <div class="flex flex-wrap justify-center items-center gap-2 max-w-lg mb-6">
                    <span class="px-3 py-1 rounded-full bg-slate-900/90 border border-teal-500/30 text-teal-300 text-xs font-semibold">✨ Kain Kasur & Busa</span>
                    <span class="px-3 py-1 rounded-full bg-slate-900/90 border border-teal-500/30 text-teal-300 text-xs font-semibold">🛏️ Bantal, Guling & Sprei</span>
                    <span class="px-3 py-1 rounded-full bg-slate-900/90 border border-teal-500/30 text-teal-300 text-xs font-semibold">🧸 Kain Boneka / Rasfur & Velboa</span>
                    <span class="px-3 py-1 rounded-full bg-slate-900/90 border border-teal-500/30 text-teal-300 text-xs font-semibold">🧵 Aneka Bahan & Motif Lengkap</span>
                </div>

                <div class="px-6 py-2.5 rounded-full bg-slate-900/80 border border-slate-800 text-slate-400 text-sm font-medium flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-400 animate-pulse"></span>
                    Kasir siap melayani transaksi Anda
                </div>
            </div>

            <!-- 2. VIEW ACTIVE (Sedang Transaksi / Ada Barang) -->
            <div id="viewActive" class="hidden flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                
                <!-- Left: Items List (7 Cols on desktop) -->
                <div class="lg:col-span-7 flex flex-col bg-slate-900/70 border border-slate-800/90 rounded-2xl p-4 sm:p-5 overflow-hidden">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm sm:text-base font-bold text-slate-200">Daftar Barang</span>
                            <span id="activeItemsBadge" class="px-2 py-0.5 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold">0 Item</span>
                        </div>
                        <div id="activeCustomerName" class="text-xs sm:text-sm font-semibold text-emerald-400"></div>
                    </div>

                    <!-- Item Rows Container -->
                    <div id="itemsContainer" class="flex-1 overflow-y-auto space-y-2.5 max-h-[380px] lg:max-h-[440px] pr-1">
                        <!-- Dynamic item rows injected here -->
                    </div>
                </div>

                <!-- Right: Total & Payment Jumbo Display (5 Cols on desktop) -->
                <div class="lg:col-span-5 flex flex-col justify-between gap-4">
                    
                    <!-- Jumbo Total Card -->
                    <div class="flex-1 flex flex-col justify-center bg-gradient-to-br from-slate-900 via-slate-900 to-teal-950/40 border-2 border-teal-500/40 rounded-3xl p-6 sm:p-8 glow-box text-center relative overflow-hidden">
                        
                        <!-- Ambient lighting -->
                        <div class="absolute -top-20 -right-20 w-40 h-40 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>

                        <span class="text-xs sm:text-sm uppercase tracking-widest font-black text-teal-400 mb-1">
                            TOTAL NOMINAL BELANJA
                        </span>
                        
                        <div id="activeTotalAmount" class="font-mono text-3xl sm:text-5xl lg:text-6xl font-black text-emerald-400 tracking-tight glow-emerald my-2 sm:my-3">
                            Rp 0
                        </div>

                        <div id="activeSummaryQty" class="text-xs sm:text-sm text-slate-400 font-medium">
                            0 barang terdaftar
                        </div>
                    </div>

                    <!-- Payment Details (Cash / Change / DP) -->
                    <div id="paymentDetailsCard" class="hidden bg-slate-900/80 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-2.5">
                        
                        <div id="receivedRow" class="flex items-center justify-between text-sm sm:text-base">
                            <span id="receivedLabel" class="text-slate-400 font-medium">Uang Diterima:</span>
                            <span id="receivedVal" class="font-mono font-bold text-slate-200">Rp 0</span>
                        </div>

                        <div id="changeRow" class="flex items-center justify-between pt-2 border-t border-slate-800">
                            <span class="text-base sm:text-lg font-black text-amber-400">KEMBALIAN:</span>
                            <span id="changeVal" class="font-mono text-xl sm:text-2xl font-black text-amber-300 glow-amber">Rp 0</span>
                        </div>

                        <div id="creditRow" class="hidden flex items-center justify-between pt-2 border-t border-slate-800">
                            <span class="text-sm sm:text-base font-bold text-rose-400">Sisa Piutang:</span>
                            <span id="creditVal" class="font-mono text-base sm:text-lg font-black text-rose-300">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. VIEW SUCCESS (Transaksi Selesai & Rincian Belanja) -->
            <div id="viewSuccess" class="hidden flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch w-full">
                
                <!-- Left: Purchased Items List (7 Cols on desktop) -->
                <div class="lg:col-span-7 flex flex-col bg-slate-900/70 border border-slate-800/90 rounded-2xl p-4 sm:p-5 overflow-hidden order-2 lg:order-1">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm sm:text-base font-bold text-slate-200">Daftar Barang Belanjaan</span>
                            <span id="successItemsBadge" class="px-2 py-0.5 rounded-full bg-teal-500/20 text-teal-300 text-xs font-bold">0 Item</span>
                        </div>
                        <div id="successCustomerName" class="text-xs sm:text-sm font-semibold text-emerald-400"></div>
                    </div>

                    <!-- Item Rows Container for Success View -->
                    <div id="successItemsContainer" class="flex-1 overflow-y-auto space-y-2.5 max-h-[380px] lg:max-h-[440px] pr-1">
                        <!-- Dynamic item rows injected here -->
                    </div>
                </div>

                <!-- Right: Payment Receipt & Confirmation (5 Cols on desktop) -->
                <div class="lg:col-span-5 flex flex-col justify-between gap-4 order-1 lg:order-2">
                    
                    <!-- Success Status & Total Card -->
                    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950/40 border-2 border-emerald-500/40 rounded-3xl p-6 sm:p-7 glow-box text-center relative overflow-hidden">
                        
                        <!-- Checkmark Icon -->
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-emerald-500/20 border-2 border-emerald-400/50 flex items-center justify-center text-emerald-400 mx-auto mb-3 shadow-lg shadow-emerald-500/20">
                            <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight mb-1">
                            Pembayaran Berhasil!
                        </h2>
                        <div id="successTrxNo" class="inline-block px-3 py-1 rounded-full bg-slate-950/80 border border-slate-800 text-xs sm:text-sm font-mono font-bold text-teal-400 mb-3">
                            No. Nota: -
                        </div>

                        <!-- Jumbo Total -->
                        <div class="pt-3 border-t border-slate-800/80">
                            <span class="text-xs uppercase tracking-widest font-extrabold text-teal-400">
                                TOTAL TRANSAKSI
                            </span>
                            <div id="successTotal" class="font-mono text-3xl sm:text-4xl lg:text-5xl font-black text-emerald-400 tracking-tight glow-emerald my-2">
                                Rp 0
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary Box -->
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 sm:p-5 space-y-2.5">
                        <div id="successReceivedRow" class="flex justify-between items-center text-sm sm:text-base text-slate-300">
                            <span id="successReceivedLabel" class="text-slate-400 font-medium">Uang Diterima:</span>
                            <span id="successReceived" class="font-mono font-bold text-slate-200">Rp 0</span>
                        </div>

                        <div id="successChangeRow" class="flex justify-between items-center pt-2 border-t border-slate-800">
                            <span class="text-base sm:text-lg font-black text-amber-400">KEMBALIAN:</span>
                            <span id="successChange" class="font-mono text-xl sm:text-2xl font-black text-amber-300 glow-amber">Rp 0</span>
                        </div>

                        <div id="successCreditRow" class="hidden flex justify-between items-center pt-2 border-t border-slate-800">
                            <span class="text-sm sm:text-base font-bold text-rose-400">Sisa Piutang:</span>
                            <span id="successCredit" class="font-mono text-base sm:text-lg font-black text-rose-300">Rp 0</span>
                        </div>
                    </div>

                    <!-- Thank you note -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/60 text-center">
                        <p class="text-xs sm:text-sm font-bold text-emerald-400 flex items-center justify-center gap-1.5">
                            <span>✨</span>
                            <span>Terima Kasih Telah Berbelanja di Toko Kain Bu Ulil</span>
                            <span>✨</span>
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>
                © {{ date('Y') }} Toko Kain Bu Ulil · Sistem Kasir Pintar
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                <span>Real-Time Customer Display</span>
            </div>
        </footer>
    </div>

    <!-- Script Controller -->
    <script>
        // State Holder
        let currentState = {
            status: 'idle',
            cart: [],
            total_amount: 0,
            items_count: 0,
            last_item: null,
            payment_method: null,
            received_amount: null,
            change_amount: null,
            down_payment: null,
            remaining_credit: null,
            transaction_number: null,
            party_name: null,
            timestamp: 0
        };

        // Format Helper
        function formatRupiah(num) {
            if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
            return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function formatQty(qty) {
            const n = parseFloat(qty) || 0;
            return (n % 1 === 0) ? n.toFixed(0) : n.toFixed(2).replace('.', ',');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renderItemCard(item, isHighlighted) {
            const name = item.name || item.product_name || 'Produk';
            const price = parseFloat(item.price) || 0;
            const qty = parseFloat(item.quantity) || 1;
            const subtotal = parseFloat(item.subtotal) || (price * qty);
            const notes = item.notes ? String(item.notes).trim() : '';

            return `
                <div class="flex items-start justify-between p-3 rounded-xl bg-slate-950/60 border ${isHighlighted ? 'border-teal-500/50 bg-teal-950/20 item-row-new' : 'border-slate-800/80'} transition">
                    <div class="flex-1 pr-3">
                        <div class="font-bold text-sm sm:text-base text-white leading-snug">
                            ${escapeHtml(name)}
                        </div>
                        ${notes ? `<div class="text-xs text-amber-400 font-medium mt-0.5">* ${escapeHtml(notes)}</div>` : ''}
                        <div class="text-xs sm:text-sm text-slate-400 mt-1 flex items-center gap-2">
                            <span class="font-semibold text-teal-400">${formatQty(qty)}x</span>
                            <span>@ ${formatRupiah(price)}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-mono font-black text-sm sm:text-base text-emerald-400">
                            ${formatRupiah(subtotal)}
                        </span>
                    </div>
                </div>
            `;
        }

        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeEl = document.getElementById('liveTime');
            const dateEl = document.getElementById('liveDate');
            
            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Screen Wake Lock to keep phone screen ON
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    await navigator.wakeLock.request('screen');
                    console.log('Screen Wake Lock active');
                }
            } catch (err) {
                console.warn('Wake Lock error:', err);
            }
        }
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') requestWakeLock();
        });
        requestWakeLock();

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }

        // Render Function
        function renderState(state) {
            if (!state) return;
            currentState = { ...currentState, ...state };

            // Ensure cart is strictly a JavaScript Array
            if (currentState.cart) {
                if (Array.isArray(currentState.cart)) {
                    // Valid array
                } else if (typeof currentState.cart === 'object') {
                    currentState.cart = Object.values(currentState.cart);
                } else {
                    currentState.cart = [];
                }
            } else {
                currentState.cart = [];
            }

            const viewIdle = document.getElementById('viewIdle');
            const viewActive = document.getElementById('viewActive');
            const viewSuccess = document.getElementById('viewSuccess');

            const hasItems = currentState.cart.length > 0;
            const status = currentState.status || (hasItems ? 'active' : 'idle');

            const itemsCount = currentState.cart.reduce((sum, item) => sum + (parseFloat(item.quantity) || 1), 0);

            if (status === 'success') {
                viewIdle.classList.add('hidden');
                viewActive.classList.add('hidden');
                viewSuccess.classList.remove('hidden');

                document.getElementById('successTrxNo').textContent = 'No. Nota: ' + (currentState.transaction_number || '-');
                document.getElementById('successTotal').textContent = formatRupiah(currentState.total_amount);
                document.getElementById('successCustomerName').textContent = currentState.party_name ? `Pelanggan: ${currentState.party_name}` : '';
                document.getElementById('successItemsBadge').textContent = `${currentState.cart.length} Produk (${formatQty(itemsCount)} Qty)`;

                // Render Success Item Rows
                const successContainer = document.getElementById('successItemsContainer');
                if (successContainer) {
                    if (currentState.cart.length > 0) {
                        successContainer.innerHTML = currentState.cart.map(item => renderItemCard(item, false)).join('');
                    } else {
                        successContainer.innerHTML = `
                            <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-sm text-center">
                                <span>Rincian barang tercatat pada sistem kasir.</span>
                            </div>
                        `;
                    }
                }

                const succReceivedRow = document.getElementById('successReceivedRow');
                const succChangeRow = document.getElementById('successChangeRow');
                const succCreditRow = document.getElementById('successCreditRow');

                if (currentState.payment_method === 'cash' || currentState.payment_method === 'split') {
                    succReceivedRow.classList.remove('hidden');
                    succChangeRow.classList.remove('hidden');
                    succCreditRow.classList.add('hidden');
                    document.getElementById('successReceivedLabel').textContent = 'Uang Diterima:';
                    document.getElementById('successReceived').textContent = formatRupiah(currentState.received_amount);
                    document.getElementById('successChange').textContent = formatRupiah(currentState.change_amount);
                } else if (['credit_cash', 'credit_transfer', 'credit_split', 'receivable'].includes(currentState.payment_method)) {
                    succReceivedRow.classList.remove('hidden');
                    document.getElementById('successReceivedLabel').textContent = 'Uang Muka (DP):';
                    document.getElementById('successReceived').textContent = formatRupiah(currentState.down_payment || 0);
                    succChangeRow.classList.add('hidden');
                    succCreditRow.classList.remove('hidden');
                    document.getElementById('successCredit').textContent = formatRupiah(currentState.remaining_credit || currentState.total_amount);
                } else {
                    succReceivedRow.classList.add('hidden');
                    succChangeRow.classList.add('hidden');
                    succCreditRow.classList.add('hidden');
                }

            } else if (hasItems || status === 'active' || status === 'payment') {
                viewIdle.classList.add('hidden');
                viewSuccess.classList.add('hidden');
                viewActive.classList.remove('hidden');

                // Update Items Container
                const container = document.getElementById('itemsContainer');
                document.getElementById('activeItemsBadge').textContent = `${currentState.cart.length} Produk (${formatQty(itemsCount)} Qty)`;
                document.getElementById('activeSummaryQty').textContent = `${currentState.cart.length} jenis produk (${formatQty(itemsCount)} total qty)`;
                document.getElementById('activeCustomerName').textContent = currentState.party_name ? `Pelanggan: ${currentState.party_name}` : '';

                // Render Item Cards
                if (currentState.cart.length > 0) {
                    container.innerHTML = currentState.cart.map((item, index) => {
                        const isLast = (index === currentState.cart.length - 1);
                        return renderItemCard(item, isLast);
                    }).reverse().join('');
                } else {
                    container.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-sm text-center">
                            <span>Menunggu kasir menambahkan produk...</span>
                        </div>
                    `;
                }

                // Update Total Amount
                document.getElementById('activeTotalAmount').textContent = formatRupiah(currentState.total_amount);

                // Update Payment Card if there's payment input
                const paymentCard = document.getElementById('paymentDetailsCard');
                const receivedRow = document.getElementById('receivedRow');
                const changeRow = document.getElementById('changeRow');
                const creditRow = document.getElementById('creditRow');

                if (currentState.received_amount !== null && currentState.received_amount !== undefined && currentState.received_amount > 0) {
                    paymentCard.classList.remove('hidden');
                    receivedRow.classList.remove('hidden');
                    document.getElementById('receivedLabel').textContent = 'Uang Diterima:';
                    document.getElementById('receivedVal').textContent = formatRupiah(currentState.received_amount);

                    const change = (currentState.change_amount !== null && currentState.change_amount !== undefined)
                        ? currentState.change_amount
                        : Math.max(0, currentState.received_amount - currentState.total_amount);
                    document.getElementById('changeVal').textContent = formatRupiah(change);
                    changeRow.classList.remove('hidden');
                    creditRow.classList.add('hidden');
                } else if (['credit_cash', 'credit_transfer', 'credit_split'].includes(currentState.payment_method) && (currentState.down_payment > 0 || currentState.cash_amount > 0 || currentState.transfer_amount > 0)) {
                    paymentCard.classList.remove('hidden');
                    receivedRow.classList.remove('hidden');
                    document.getElementById('receivedLabel').textContent = 'Uang Muka (DP):';
                    document.getElementById('receivedVal').textContent = formatRupiah(currentState.down_payment || currentState.cash_amount || currentState.transfer_amount);
                    changeRow.classList.add('hidden');
                    creditRow.classList.remove('hidden');
                    document.getElementById('creditVal').textContent = formatRupiah(currentState.remaining_credit);
                } else {
                    paymentCard.classList.add('hidden');
                }

            } else {
                // IDLE
                viewIdle.classList.remove('hidden');
                viewActive.classList.add('hidden');
                viewSuccess.classList.add('hidden');
            }
        }

        // 1. Listen via Local BroadcastChannel (0ms delay for same PC dual monitor)
        try {
            const channel = new BroadcastChannel('pos_customer_display');
            channel.onmessage = (event) => {
                if (event.data) {
                    renderState(event.data);
                }
            };
        } catch (e) {
            console.warn('BroadcastChannel not supported:', e);
        }

        // 2. Listen via Server-Sent Events (SSE) (for separate Phone/Tablet over Wi-Fi)
        let evtSource = null;
        function initSSE() {
            try {
                if (evtSource) evtSource.close();
                evtSource = new EventSource('/api/customer-display/stream');

                evtSource.addEventListener('state_update', (e) => {
                    if (e.data) {
                        try {
                            const data = JSON.parse(e.data);
                            renderState(data);
                            setConnectionStatus(true);
                        } catch (err) {
                            console.error('SSE JSON error', err);
                        }
                    }
                });

                evtSource.onopen = () => setConnectionStatus(true);
                evtSource.onerror = () => {
                    setConnectionStatus(false);
                    evtSource.close();
                    setTimeout(initSSE, 3000);
                };
            } catch (e) {
                console.warn('SSE error:', e);
                // Fallback to polling
                setInterval(fetchState, 1500);
            }
        }

        function setConnectionStatus(isConnected) {
            const badge = document.getElementById('connectionBadge');
            const text = document.getElementById('connectionText');
            if (isConnected) {
                badge.className = 'flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold';
                text.textContent = 'Terhubung';
            } else {
                badge.className = 'flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold';
                text.textContent = 'Menghubungkan...';
            }
        }

        let lastStateSignature = '';

        // 3. Regular Polling Fetch (Every 800ms for 100% reliable cross-device sync)
        async function fetchState() {
            try {
                const res = await fetch('/api/customer-display/state?_t=' + Date.now(), { cache: 'no-store' });
                if (res.ok) {
                    const data = await res.json();
                    if (data) {
                        const signature = JSON.stringify(data);
                        if (signature !== lastStateSignature) {
                            lastStateSignature = signature;
                            renderState(data);
                        }
                    }
                    setConnectionStatus(true);
                } else {
                    setConnectionStatus(false);
                }
            } catch (e) {
                setConnectionStatus(false);
            }
        }

        // Start connection & continuous polling
        fetchState();
        setInterval(fetchState, 800);
        initSSE();
    </script>
</body>
</html>
