<?php

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppGatewayInterface
{
    /**
     * Send an outgoing WhatsApp message.
     */
    public function sendMessage(string $target, string $message): bool;

    /**
     * Parse incoming webhook payload into normalized sender and message.
     *
     * @return array{sender: string, message: string}|null
     */
    public function parseWebhook(array $payload): ?array;
}
