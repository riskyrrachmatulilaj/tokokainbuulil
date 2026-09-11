<?php

namespace App\Services\WhatsApp\Gateways;

use App\Services\WhatsApp\Contracts\WhatsAppGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteGateway implements WhatsAppGatewayInterface
{
    protected string $apiToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiToken = (string) config('whatsapp.gateways.fonnte.api_token', '');
        $this->apiUrl = (string) config('whatsapp.gateways.fonnte.api_url', 'https://api.fonnte.com/send');
    }

    public function sendMessage(string $target, string $message): bool
    {
        if (empty($this->apiToken)) {
            Log::info("WhatsApp Fonnte (Mock/No Token) to {$target}: {$message}");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->timeout(15)->post($this->apiUrl, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Fonnte API send message failed', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'target' => $target,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Fonnte send exception: '.$e->getMessage(), [
                'target' => $target,
            ]);

            return false;
        }
    }

    public function parseWebhook(array $payload): ?array
    {
        $sender = $payload['sender'] ?? $payload['from'] ?? $payload['phone'] ?? null;
        $message = $payload['message'] ?? $payload['text'] ?? $payload['body'] ?? null;

        if (empty($sender) || !is_string($sender) || $message === null || !is_string($message)) {
            return null;
        }

        return [
            'sender' => trim($sender),
            'message' => trim($message),
        ];
    }
}
