<?php

namespace Database\Seeders;

use App\Models\CollectivePayment;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\PaymentHistory;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivableCollectivePayment;
use App\Models\ReceivableInstallment;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use App\Models\Sale;
use App\Models\User;
use App\Services\CollectivePaymentService;
use App\Services\DebtService;
use App\Services\PaymentService;
use App\Services\ReceivableCollectivePaymentService;
use App\Services\ReceivablePaymentService;
use App\Services\ReceivableService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $admin = User::where('email', 'admin@hutang.test')->first();
        $kasir = User::where('email', 'kasir@hutang.test')->first();

        if (env('SEED_DUMMY_DATA', true) === false || env('SEED_DUMMY_DATA', 'true') === 'false') {
            $this->command?->info('Seeding data dummy dilewati karena SEED_DUMMY_DATA=false.');
            return;
        }

        $hasHutang = Customer::exists();
        $hasPiutang = ReceivableParty::exists();
        $hasPenjualan = Product::exists();

        if ($hasHutang && $hasPiutang && $hasPenjualan) {
            $this->command?->info('Database sudah berisi data lengkap, seeding dilewati.');

            return;
        }

        if (! $hasHutang) {
            $this->seedHutang($admin, $kasir);
        } else {
            $this->command?->info('Data hutang sudah ada, seeding hutang dilewati.');
        }

        if (! $hasPiutang) {
            $this->seedPiutang($admin, $kasir);
        } else {
            $this->command?->info('Data piutang sudah ada, seeding piutang dilewati.');
        }

        if (! $hasPenjualan) {
            $this->seedPenjualan($admin, $kasir);
        } else {
            $this->command?->info('Data penjualan sudah ada, seeding penjualan dilewati.');
        }

        $this->command?->info('Seeding selesai.');
        $this->command?->info('Admin: admin@hutang.test / password');
        $this->command?->info('Kasir: kasir@hutang.test / password');
    }

    private function seedHutang(User $admin, User $kasir): void
    {
        $customers = collect([
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 12, Bandung'],
            ['name' => 'Siti Aminah', 'phone' => '082198765432', 'address' => 'Jl. Sudirman No. 45, Jakarta'],
            ['name' => 'Agus Wijaya', 'phone' => '083812345678', 'address' => 'Jl. Diponegoro No. 8, Semarang'],
            ['name' => 'Dewi Lestari', 'phone' => '085655443322', 'address' => 'Jl. Gajah Mada No. 21, Surabaya'],
            ['name' => 'Rudi Hartono', 'phone' => '087788990011', 'address' => 'Jl. Malioboro No. 5, Yogyakarta'],
            ['name' => 'Ratna Sari', 'phone' => '089512345678', 'address' => 'Jl. Ahmad Yani No. 33, Medan'],
            ['name' => 'Joko Susilo', 'phone' => '081155566677', 'address' => 'Jl. Pahlawan No. 17, Malang'],
            ['name' => 'Maya Anggraini', 'phone' => '082299988877', 'address' => 'Jl. Gatot Subroto No. 9, Denpasar'],
        ]);

        $customers->each(fn (array $c) => Customer::create($c));

        $debtService = app(DebtService::class);
        $paymentService = app(PaymentService::class);
        $collectiveService = app(CollectivePaymentService::class);

        $scenarios = [
            ['customer_index' => 0, 'debts' => [
                ['amount' => 500000, 'debt_date' => now()->subMonths(2)->startOfDay(), 'due_date' => now()->subMonths(1)->startOfDay(), 'paid' => 0],
                ['amount' => 700000, 'debt_date' => now()->subMonths(1)->startOfDay(), 'due_date' => now()->addDays(5)->startOfDay(), 'paid' => 400000],
                ['amount' => 300000, 'debt_date' => now()->subDays(10)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
            ['customer_index' => 1, 'debts' => [
                ['amount' => 1000000, 'debt_date' => now()->subMonths(3)->startOfDay(), 'due_date' => now()->subMonths(1)->startOfDay(), 'paid' => 1000000],
                ['amount' => 250000, 'debt_date' => now()->subDays(20)->startOfDay(), 'due_date' => now()->addDays(10)->startOfDay(), 'paid' => 0],
            ]],
            ['customer_index' => 2, 'debts' => [
                ['amount' => 1500000, 'debt_date' => now()->subDays(45)->startOfDay(), 'due_date' => now()->subDays(15)->startOfDay(), 'paid' => 0],
                ['amount' => 450000, 'debt_date' => now()->subDays(5)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
            ['customer_index' => 3, 'debts' => [
                ['amount' => 800000, 'debt_date' => now()->subDays(30)->startOfDay(), 'due_date' => now()->addDays(20)->startOfDay(), 'paid' => 300000],
            ]],
            ['customer_index' => 4, 'debts' => [
                ['amount' => 2000000, 'debt_date' => now()->subMonths(4)->startOfDay(), 'due_date' => now()->subMonths(2)->startOfDay(), 'paid' => 0],
                ['amount' => 600000, 'debt_date' => now()->subDays(15)->startOfDay(), 'due_date' => now()->addDays(15)->startOfDay(), 'paid' => 0],
            ]],
            ['customer_index' => 5, 'debts' => [
                ['amount' => 350000, 'debt_date' => now()->subDays(60)->startOfDay(), 'due_date' => now()->subDays(30)->startOfDay(), 'paid' => 0],
            ]],
            ['customer_index' => 6, 'debts' => [
                ['amount' => 1200000, 'debt_date' => now()->subMonths(1)->startOfDay(), 'due_date' => now()->addDays(25)->startOfDay(), 'paid' => 500000],
            ]],
            ['customer_index' => 7, 'debts' => [
                ['amount' => 750000, 'debt_date' => now()->subDays(25)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
        ];

        foreach ($scenarios as $scenario) {
            $customer = Customer::orderBy('id')->get()[$scenario['customer_index']];

            foreach ($scenario['debts'] as $debtData) {
                $debt = $debtService->createDebt([
                    'customer_id' => $customer->id,
                    'amount' => $debtData['amount'],
                    'debt_date' => $debtData['debt_date'],
                    'due_date' => $debtData['due_date'],
                    'description' => $this->fakeHutangSentence(),
                ], $admin);

                $paid = $debtData['paid'];
                if ($paid <= 0) {
                    continue;
                }

                $firstPayment = min($paid, (float) $debt->remaining_amount);
                $paymentService->recordInstallment([
                    'debt_id' => $debt->id,
                    'amount' => $firstPayment,
                    'installment_date' => Carbon::parse($debtData['debt_date'])->addDays(7),
                    'description' => 'Pembayaran cicilan ke-1',
                ], $kasir);

                $remainingToPay = $paid - $firstPayment;
                if ($remainingToPay > 0 && (float) $debt->fresh()->remaining_amount > 0) {
                    $paymentService->recordInstallment([
                        'debt_id' => $debt->id,
                        'amount' => min($remainingToPay, (float) $debt->fresh()->remaining_amount),
                        'installment_date' => Carbon::parse($debtData['debt_date'])->addDays(21),
                        'description' => 'Pembayaran cicilan ke-2',
                    ], $kasir);
                }
            }
        }

        // Contoh pembayaran kolektif dengan aturan FIFO
        $collectiveCustomer = Customer::orderBy('id')->get()[0];

        $collectiveService->process([
            'customer_id' => $collectiveCustomer->id,
            'amount' => 900000,
            'payment_date' => now()->subDays(3),
            'description' => 'Pembayaran kolektif contoh (FIFO)',
        ], $kasir);
    }

    private function seedPiutang(User $admin, User $kasir): void
    {
        $parties = collect([
            ['name' => 'Toko Berkah Jaya', 'phone' => '0215550101', 'address' => 'Jl. Raya Cikarang No. 88, Bekasi'],
            ['name' => 'PT Sinar Abadi', 'phone' => '0215550102', 'address' => 'Jl. Industri No. 12, Tangerang'],
            ['name' => 'CV Maju Bersama', 'phone' => '0225550103', 'address' => 'Jl. Asia Afrika No. 5, Bandung'],
            ['name' => 'UD Sentosa', 'phone' => '0315550104', 'address' => 'Jl. Pemuda No. 7, Surabaya'],
            ['name' => 'Koperasi Karya', 'phone' => '02745550105', 'address' => 'Jl. Kaliurang No. 19, Yogyakarta'],
            ['name' => 'Rumah Makan Padang Sari', 'phone' => '07515550106', 'address' => 'Jl. Jendral Sudirman No. 3, Padang'],
            ['name' => 'Toko Elektronik Mitra', 'phone' => '0245550107', 'address' => 'Jl. Pandanaran No. 11, Semarang'],
            ['name' => 'Laundry Bersih', 'phone' => '03615550108', 'address' => 'Jl. Raya Kuta No. 24, Bali'],
        ]);

        $parties->each(fn (array $p) => ReceivableParty::create($p));

        $receivableService = app(ReceivableService::class);
        $paymentService = app(ReceivablePaymentService::class);
        $collectiveService = app(ReceivableCollectivePaymentService::class);

        $scenarios = [
            ['party_index' => 0, 'receivables' => [
                ['amount' => 4500000, 'receivable_date' => now()->subMonths(2)->startOfDay(), 'due_date' => now()->subMonths(1)->startOfDay(), 'paid' => 0],
                ['amount' => 3200000, 'receivable_date' => now()->subMonths(1)->startOfDay(), 'due_date' => now()->addDays(5)->startOfDay(), 'paid' => 1500000],
                ['amount' => 1200000, 'receivable_date' => now()->subDays(10)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
            ['party_index' => 1, 'receivables' => [
                ['amount' => 10000000, 'receivable_date' => now()->subMonths(3)->startOfDay(), 'due_date' => now()->subMonths(1)->startOfDay(), 'paid' => 10000000],
                ['amount' => 2500000, 'receivable_date' => now()->subDays(20)->startOfDay(), 'due_date' => now()->addDays(10)->startOfDay(), 'paid' => 0],
            ]],
            ['party_index' => 2, 'receivables' => [
                ['amount' => 7500000, 'receivable_date' => now()->subDays(45)->startOfDay(), 'due_date' => now()->subDays(15)->startOfDay(), 'paid' => 0],
                ['amount' => 1800000, 'receivable_date' => now()->subDays(5)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
            ['party_index' => 3, 'receivables' => [
                ['amount' => 3600000, 'receivable_date' => now()->subDays(30)->startOfDay(), 'due_date' => now()->addDays(20)->startOfDay(), 'paid' => 1200000],
            ]],
            ['party_index' => 4, 'receivables' => [
                ['amount' => 9000000, 'receivable_date' => now()->subMonths(4)->startOfDay(), 'due_date' => now()->subMonths(2)->startOfDay(), 'paid' => 0],
                ['amount' => 2400000, 'receivable_date' => now()->subDays(15)->startOfDay(), 'due_date' => now()->addDays(15)->startOfDay(), 'paid' => 0],
            ]],
            ['party_index' => 5, 'receivables' => [
                ['amount' => 1500000, 'receivable_date' => now()->subDays(60)->startOfDay(), 'due_date' => now()->subDays(30)->startOfDay(), 'paid' => 0],
            ]],
            ['party_index' => 6, 'receivables' => [
                ['amount' => 5600000, 'receivable_date' => now()->subMonths(1)->startOfDay(), 'due_date' => now()->addDays(25)->startOfDay(), 'paid' => 2000000],
            ]],
            ['party_index' => 7, 'receivables' => [
                ['amount' => 2800000, 'receivable_date' => now()->subDays(25)->startOfDay(), 'due_date' => null, 'paid' => 0],
            ]],
        ];

        foreach ($scenarios as $scenario) {
            $party = ReceivableParty::orderBy('id')->get()[$scenario['party_index']];

            foreach ($scenario['receivables'] as $receivableData) {
                $receivable = $receivableService->createReceivable([
                    'receivable_party_id' => $party->id,
                    'amount' => $receivableData['amount'],
                    'receivable_date' => $receivableData['receivable_date'],
                    'due_date' => $receivableData['due_date'],
                    'description' => $this->fakePiutangSentence(),
                ], $admin);

                $paid = $receivableData['paid'];
                if ($paid <= 0) {
                    continue;
                }

                $firstPayment = min($paid, (float) $receivable->remaining_amount);
                $paymentService->recordInstallment([
                    'receivable_id' => $receivable->id,
                    'amount' => $firstPayment,
                    'installment_date' => Carbon::parse($receivableData['receivable_date'])->addDays(7),
                    'description' => 'Penerimaan cicilan ke-1',
                ], $kasir);

                $remainingToPay = $paid - $firstPayment;
                if ($remainingToPay > 0 && (float) $receivable->fresh()->remaining_amount > 0) {
                    $paymentService->recordInstallment([
                        'receivable_id' => $receivable->id,
                        'amount' => min($remainingToPay, (float) $receivable->fresh()->remaining_amount),
                        'installment_date' => Carbon::parse($receivableData['receivable_date'])->addDays(21),
                        'description' => 'Penerimaan cicilan ke-2',
                    ], $kasir);
                }
            }
        }

        // Contoh pembayaran kolektif piutang dengan aturan FIFO
        $collectiveParty = ReceivableParty::orderBy('id')->get()[0];

        $collectiveService->process([
            'receivable_party_id' => $collectiveParty->id,
            'amount' => 4000000,
            'payment_date' => now()->subDays(2),
            'description' => 'Pembayaran kolektif contoh (FIFO)',
        ], $kasir);
    }

    private function seedPenjualan(User $admin, User $kasir): void
    {
        $products = collect([
            ['name' => 'Kain Batik Sekar Jagad', 'price' => 45000],
            ['name' => 'Kain Batik Parang', 'price' => 55000],
            ['name' => 'Kain Katun Polos Putih', 'price' => 28000],
            ['name' => 'Kain Katun Polos Hitam', 'price' => 28000],
            ['name' => 'Kain Mori Primissima', 'price' => 32000],
            ['name' => 'Kain Satin Silk', 'price' => 65000],
            ['name' => 'Kain Brokat Songket', 'price' => 125000],
            ['name' => 'Kain Tenun Troso', 'price' => 75000],
            ['name' => 'Kain Rayon Bunga', 'price' => 48000],
            ['name' => 'Kain Drill Jeans', 'price' => 60000],
        ]);

        $products->each(function (array $product) {
            Product::create(array_merge($product, [
                'description' => 'Kain berkualitas untuk kebutuhan toko.',
                'is_active' => true,
            ]));
        });

        $saleService = app(\App\Services\SaleService::class);
        $byName = fn (string $name) => Product::where('name', $name)->firstOrFail()->id;
        $firstParty = ReceivableParty::orderBy('id')->first();
        $secondParty = ReceivableParty::orderBy('id')->skip(1)->first() ?? $firstParty;

        // Penjualan tunai hari ini
        $saleService->createSale([
            'items' => [
                ['product_id' => $byName('Kain Batik Parang'), 'quantity' => 2],
                ['product_id' => $byName('Kain Katun Polos Putih'), 'quantity' => 1],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $firstParty->id,
            'received_amount' => 138000,
            'sale_date' => today(),
            'description' => 'Penjualan tunai contoh',
        ], $kasir);

        $saleService->createSale([
            'items' => [
                ['product_id' => $byName('Kain Mori Primissima'), 'quantity' => 3],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $secondParty->id,
            'received_amount' => 100000,
            'sale_date' => today(),
            'description' => null,
        ], $kasir);

        // Penjualan kredit (menjadi nota piutang debitur pertama)
        $saleService->createSale([
            'items' => [
                ['product_id' => $byName('Kain Brokat Songket'), 'quantity' => 5],
            ],
            'payment_method' => Sale::PAYMENT_METHOD_RECEIVABLE,
            'receivable_party_id' => $firstParty->id,
            'sale_date' => today(),
            'description' => 'Penjualan kredit contoh (FIFO)',
        ], $kasir);

        // Penjualan tunai kemarin (agar laporan penjualan harian kemarin tidak kosong)
        $saleService->createSale([
            'items' => [
                ['product_id' => $byName('Kain Tenun Troso'), 'quantity' => 1],
                ['product_id' => $byName('Kain Satin Silk'), 'quantity' => 2],
            ],
            'payment_method' => \App\Models\Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $secondParty->id,
            'received_amount' => 205000,
            'sale_date' => today()->subDay(),
            'description' => null,
        ], $kasir);

        $saleService->createSale([
            'items' => [
                ['product_id' => $byName('Kain Rayon Bunga'), 'quantity' => 2],
            ],
            'payment_method' => \App\Models\Sale::PAYMENT_METHOD_CASH,
            'receivable_party_id' => $firstParty->id,
            'received_amount' => 96000,
            'sale_date' => today()->subDay(),
            'description' => null,
        ], $kasir);
    }

    private function fakeHutangSentence(): string
    {
        $sentences = [
            'Pembelian barang dagangan',
            'Pembelian bahan baku',
            'Pinjaman modal usaha',
            'Pembelian perlengkapan toko',
            'Pembelian secara kredit',
            'Utang perbaikan kendaraan',
        ];

        return $sentences[array_rand($sentences)];
    }

    private function fakePiutangSentence(): string
    {
        $sentences = [
            'Penjualan barang secara kredit',
            'Penjualan bahan baku',
            'Penagihan jasa supplier',
            'Pembayaran termin ke-1',
            'Piutang atas pengiriman barang',
            'Penjualan perlengkapan toko',
        ];

        return $sentences[array_rand($sentences)];
    }
}
