<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsAppBotService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager,
        protected WhatsAppBotService $botService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = config('whatsapp.webhook_secret');
        if (! empty($secret)) {
            $incomingSecret = $request->header('X-Webhook-Secret') ?? $request->query('secret');
            if ($incomingSecret !== $secret) {
                return response()->json(['status' => 'unauthorized', 'message' => 'Invalid webhook secret.'], 403);
            }
        }

        $gateway = $this->whatsAppManager->gateway();
        $parsed = $gateway->parseWebhook($request->all());

        if (! $parsed) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'No valid sender or message found in payload.',
            ], 200);
        }

        $sender = $parsed['sender'];
        $incomingMessage = $parsed['message'];

        Log::info("WhatsApp incoming from {$sender}: {$incomingMessage}");

        $reply = $this->botService->handleIncoming($sender, $incomingMessage);

        $gateway->sendMessage($sender, $reply);

        return response()->json([
            'status' => 'success',
            'sender' => $sender,
            'reply' => $reply,
        ]);
    }
}
