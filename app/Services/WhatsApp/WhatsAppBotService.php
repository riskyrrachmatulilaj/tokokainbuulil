<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Services\SaleReportService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

class WhatsAppBotService
{
    public function __construct(
        protected SaleReportService $saleReportService
    ) {}

    public function handleIncoming(string $sender, string $message): string
    {
        $cleanMessage = trim($message);

        if ($cleanMessage === '') {
            return $this->handleMenu();
        }

        if (! $this->isAuthorized($sender)) {
            return "Akses ditolak. Nomor WhatsApp Anda ({$sender}) belum terdaftar sebagai admin toko.";
        }

        $lower = mb_strtolower($cleanMessage);

        if (in_array($lower, ['menu', 'bantuan', 'help', 'halo', 'hai', 'p', 'info', '?'], true)) {
            return $this->handleMenu();
        }

        if (preg_match('/^(?:cek\s+)?(?:hutang\s+supplier|supplier)\s+(.+)$/i', $cleanMessage, $matches)) {
            return $this->handleHutangSupplier(trim($matches[1]));
        }

        if (preg_match('/^(?:cek\s+)?(?:hutang|piutang)(?:\s+pelanggan)?\s+(.+)$/i', $cleanMessage, $matches)) {
            return $this->handleHutangPelanggan(trim($matches[1]));
        }

        if (preg_match('/^(?:cek\s+)?(?:penjualan|omset|omzet)(?:\s+(.+))?$/i', $cleanMessage, $matches)) {
            return $this->handlePenjualan($matches[1] ?? null);
        }

        if (preg_match('/^(?:cek\s+)?jatuh\s+tempo$/i', $cleanMessage)) {
            return $this->handleJatuhTempo();
        }

        if (preg_match('/^(?:cek\s+)?(?:stok|produk|barang)\s+(.+)$/i', $cleanMessage, $matches)) {
            return $this->handleStok(trim($matches[1]));
        }

        return $this->handleFallback($cleanMessage);
    }

    public function isAuthorized(string $sender): bool
    {
        $allowed = config('whatsapp.authorized_numbers', []);

        if (empty($allowed)) {
            return true;
        }

        $normalizedSender = $this->normalizePhone($sender);

        foreach ($allowed as $number) {
            if ($this->normalizePhone($number) === $normalizedSender) {
                return true;
            }
        }

        return false;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        if (str_starts_with($digits, '8')) {
            return '0' . $digits;
        }

        return $digits;
    }

    protected function handleMenu(): string
    {
        $lines = [
            "*BOT TOKO KAIN BU ULIL*",
            "Asisten Informasi Finansial & Toko",
            "",
            "Silakan ketik perintah berikut:",
            "",
            "1. *hutang [nama]*",
            "   Cek sisa piutang/hutang pelanggan.",
            "   Contoh: _hutang budi_",
            "",
            "2. *supplier [nama]*",
            "   Cek hutang toko ke supplier kain.",
            "   Contoh: _supplier maju textile_",
            "",
            "3. *penjualan [waktu]*",
            "   Cek total omset & ringkasan penjualan.",
            "   Contoh: _penjualan hari ini_, _penjualan kemarin_, _penjualan 2026-03-10_",
            "",
            "4. *jatuh tempo*",
            "   Daftar piutang yang sudah jatuh tempo.",
            "",
            "5. *stok [nama]*",
            "   Cek harga dan ketersediaan kain.",
            "   Contoh: _stok katun_",
            "",
            "Ketik *menu* kapan saja untuk membuka panduan ini.",
        ];

        return implode("\n", $lines);
    }

    protected function handleHutangPelanggan(string $term): string
    {
        $parties = ReceivableParty::search($term)
            ->with(['unpaidReceivables'])
            ->take(5)
            ->get();

        if ($parties->isEmpty()) {
            return "Pelanggan dengan kata kunci *\"{$term}\"* tidak ditemukan.";
        }

        $output = [];
        $output[] = "*INFORMASI PIUTANG PELANGGAN*";
        $output[] = "Pencarian: \"{$term}\"";
        $output[] = "";

        foreach ($parties as $party) {
            $unpaidList = $party->unpaidReceivables;
            $totalUnpaid = $unpaidList->sum('remaining_amount');

            $output[] = "Nama: *{$party->name}*";
            if (! empty($party->phone)) {
                $output[] = "No. HP: {$party->phone}";
            }
            $output[] = "Total Belum Lunas: *" . rupiah($totalUnpaid) . "*";

            if ($unpaidList->isEmpty()) {
                $output[] = "Status: Semua faktur lunas";
            } else {
                $output[] = "Faktur Belum Lunas (" . $unpaidList->count() . " faktur):";
                foreach ($unpaidList->take(5) as $rec) {
                    $dueText = $rec->due_date ? " (Jatuh tempo: " . $rec->due_date->format('d/m/Y') . ")" : "";
                    $output[] = "  • {$rec->invoice_number}: " . rupiah($rec->remaining_amount) . $dueText;
                }

                if ($unpaidList->count() > 5) {
                    $sisa = $unpaidList->count() - 5;
                    $output[] = "  • ... dan {$sisa} faktur lainnya.";
                }
            }

            $output[] = "--------------------------------";
        }

        return implode("\n", $output);
    }

    protected function handleHutangSupplier(string $term): string
    {
        $suppliers = Customer::search($term)
            ->with(['unpaidDebts'])
            ->take(5)
            ->get();

        if ($suppliers->isEmpty()) {
            return "Supplier dengan kata kunci *\"{$term}\"* tidak ditemukan.";
        }

        $output = [];
        $output[] = "*INFORMASI HUTANG SUPPLIER*";
        $output[] = "Pencarian: \"{$term}\"";
        $output[] = "";

        foreach ($suppliers as $supplier) {
            $unpaidList = $supplier->unpaidDebts;
            $totalUnpaid = $unpaidList->sum('remaining_amount');

            $output[] = "Supplier: *{$supplier->name}*";
            if (! empty($supplier->phone)) {
                $output[] = "No. HP: {$supplier->phone}";
            }
            $output[] = "Total Hutang Toko: *" . rupiah($totalUnpaid) . "*";

            if ($unpaidList->isEmpty()) {
                $output[] = "Status: Hutang toko lunas";
            } else {
                $output[] = "Tagihan Belum Lunas (" . $unpaidList->count() . " tagihan):";
                foreach ($unpaidList->take(5) as $debt) {
                    $dueText = $debt->due_date ? " (Jatuh tempo: " . $debt->due_date->format('d/m/Y') . ")" : "";
                    $output[] = "  • {$debt->invoice_number}: " . rupiah($debt->remaining_amount) . $dueText;
                }

                if ($unpaidList->count() > 5) {
                    $sisa = $unpaidList->count() - 5;
                    $output[] = "  • ... dan {$sisa} tagihan lainnya.";
                }
            }

            $output[] = "--------------------------------";
        }

        return implode("\n", $output);
    }

    protected function handlePenjualan(?string $param): string
    {
        $date = Carbon::today();
        $dateLabel = "Hari Ini (" . $date->translatedFormat('d F Y') . ")";

        if ($param !== null && trim($param) !== '') {
            $normalized = trim($param);
            if (in_array(mb_strtolower($normalized), ['kemarin', 'yesterday'], true)) {
                $date = Carbon::yesterday();
                $dateLabel = "Kemarin (" . $date->translatedFormat('d F Y') . ")";
            } elseif (! in_array(mb_strtolower($normalized), ['hari ini', 'today'], true)) {
                try {
                    $date = Carbon::parse($normalized);
                    $dateLabel = $date->translatedFormat('d F Y');
                } catch (InvalidFormatException) {
                    return "Format tanggal tidak dikenali. Gunakan format YYYY-MM-DD atau ketik 'penjualan hari ini'.";
                }
            }
        }

        $report = $this->saleReportService->data($date->format('Y-m-d'));
        $sum = $report['summary'];

        $lines = [
            "*LAPORAN PENJUALAN TOKO*",
            "Periode: *{$dateLabel}*",
            "",
            "• Total Transaksi: {$sum['transactions']} transaksi",
            "• Total Omset: *" . rupiah($sum['total_revenue']) . "*",
            "• Jumlah Item Terjual: " . formatQuantity($sum['items_count']),
            "",
            "*Rincian Penerimaan:*",
            "• Kas / Tunai: " . rupiah($sum['cash_revenue']),
            "• Transfer Bank: " . rupiah($sum['transfer_revenue']),
            "• Piutang / Tempo: " . rupiah($sum['receivable_revenue']),
        ];

        return implode("\n", $lines);
    }

    protected function handleJatuhTempo(): string
    {
        $today = Carbon::today();

        $overdue = Receivable::query()
            ->where('status', Receivable::STATUS_UNPAID)
            ->whereDate('due_date', '<=', $today)
            ->with('party')
            ->orderBy('due_date')
            ->take(10)
            ->get();

        if ($overdue->isEmpty()) {
            return "Tidak ada piutang pelanggan yang jatuh tempo per hari ini (" . $today->format('d/m/Y') . ").";
        }

        $totalOverdue = $overdue->sum('remaining_amount');

        $output = [];
        $output[] = "*TAGIHAN JATUH TEMPO / MENUNGGAK*";
        $output[] = "Per tanggal: " . $today->format('d/m/Y');
        $output[] = "Total Tertunggak: *" . rupiah($totalOverdue) . "*";
        $output[] = "";

        foreach ($overdue as $item) {
            $customerName = $item->party?->name ?? 'Tanpa Nama';
            $dueDateStr = $item->due_date ? $item->due_date->format('d/m/Y') : '-';
            $daysDiff = $item->due_date ? (int) $item->due_date->diffInDays($today, false) : 0;
            $overdueTag = $daysDiff > 0 ? " (Lewat {$daysDiff} hari)" : " (Hari ini)";

            $output[] = "• *{$customerName}*";
            $output[] = "  Faktur: {$item->invoice_number}";
            $output[] = "  Sisa: *" . rupiah($item->remaining_amount) . "*";
            $output[] = "  Jatuh Tempo: {$dueDateStr}{$overdueTag}";
            $output[] = "";
        }

        return trim(implode("\n", $output));
    }

    protected function handleStok(string $term): string
    {
        $products = Product::query()
            ->where('name', 'like', "%{$term}%")
            ->take(5)
            ->get();

        if ($products->isEmpty()) {
            return "Produk kain dengan kata kunci *\"{$term}\"* tidak ditemukan.";
        }

        $output = [];
        $output[] = "*INFORMASI STOK & HARGA KAIN*";
        $output[] = "Pencarian: \"{$term}\"";
        $output[] = "";

        foreach ($products as $product) {
            $output[] = "• *{$product->name}*";
            $output[] = "  Harga: " . rupiah($product->price);
            $output[] = "  " . $product->stock_label;
            $output[] = "  Status: " . $product->status_label;
            $output[] = "";
        }

        return trim(implode("\n", $output));
    }

    protected function handleFallback(string $message): string
    {
        return "Perintah tidak dikenali: \"{$message}\"\n\nKetik *menu* untuk melihat panduan perintah yang tersedia.";
    }
}
