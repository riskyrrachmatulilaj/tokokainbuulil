<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDisplayController extends Controller
{
    private const CACHE_KEY = 'pos_customer_display_state';
    private const CACHE_TIMESTAMP_KEY = 'pos_customer_display_timestamp';

    /**
     * Tampilan Layar Pelanggan (Customer Facing Display).
     */
    public function index()
    {
        return view('customer-display');
    }

    /**
     * Menerima pembaruan status dari Layar Kasir.
     */
    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'status' => 'required|string|in:idle,active,payment,success',
            'cart' => 'nullable|array',
            'total_amount' => 'nullable|numeric',
            'items_count' => 'nullable|numeric',
            'last_item' => 'nullable|array',
            'payment_method' => 'nullable|string',
            'received_amount' => 'nullable|numeric',
            'change_amount' => 'nullable|numeric',
            'down_payment' => 'nullable|numeric',
            'remaining_credit' => 'nullable|numeric',
            'transaction_number' => 'nullable|string',
            'party_name' => 'nullable|string',
        ]);

        if (isset($payload['cart']) && is_array($payload['cart'])) {
            $payload['cart'] = array_values($payload['cart']);
        } else {
            $payload['cart'] = [];
        }

        $timestamp = microtime(true);
        $payload['updated_at'] = now()->toIso8601String();
        $payload['timestamp'] = $timestamp;

        Cache::put(self::CACHE_KEY, $payload, now()->addHours(6));
        Cache::put(self::CACHE_TIMESTAMP_KEY, $timestamp, now()->addHours(6));

        return response()->json([
            'success' => true,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Mengambil status terkini (untuk polling / initial load).
     */
    public function state(): JsonResponse
    {
        $state = Cache::get(self::CACHE_KEY, $this->defaultState());

        return response()->json($state);
    }

    /**
     * Server-Sent Events (SSE) stream untuk update real-time dengan latensi rendah.
     */
    public function stream(): StreamedResponse
    {
        return new StreamedResponse(function () {
            // Disable output buffering
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $lastSentTimestamp = 0.0;
            $heartbeatCounter = 0;

            // Stream for max 45 seconds per connection, then let browser reconnect cleanly
            $maxExecutionTime = time() + 45;

            while (time() < $maxExecutionTime) {
                if (connection_aborted()) {
                    break;
                }

                $currentTimestamp = (float) Cache::get(self::CACHE_TIMESTAMP_KEY, 0.0);

                if ($currentTimestamp > $lastSentTimestamp || $lastSentTimestamp === 0.0) {
                    $state = Cache::get(self::CACHE_KEY, $this->defaultState());
                    $lastSentTimestamp = $currentTimestamp;

                    echo "event: state_update\n";
                    echo 'data: ' . json_encode($state) . "\n\n";
                    flush();
                }

                $heartbeatCounter++;
                if ($heartbeatCounter % 15 === 0) {
                    // Send heartbeat comment every 3 seconds to keep connection alive
                    echo ": heartbeat\n\n";
                    flush();
                }

                // Sleep for 200ms
                usleep(200000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * State awal saat kasir belum aktif / keranjang kosong.
     */
    private function defaultState(): array
    {
        return [
            'status' => 'idle',
            'cart' => [],
            'total_amount' => 0,
            'items_count' => 0,
            'last_item' => null,
            'payment_method' => null,
            'received_amount' => null,
            'change_amount' => null,
            'down_payment' => null,
            'remaining_credit' => null,
            'transaction_number' => null,
            'party_name' => null,
            'updated_at' => now()->toIso8601String(),
            'timestamp' => microtime(true),
        ];
    }
}
