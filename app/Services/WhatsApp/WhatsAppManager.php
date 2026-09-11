<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\Services\WhatsApp\Gateways\FonnteGateway;
use InvalidArgumentException;

class WhatsAppManager
{
    protected ?WhatsAppGatewayInterface $driver = null;

    public function gateway(?string $name = null): WhatsAppGatewayInterface
    {
        $driverName = $name ?: config('whatsapp.default', 'fonnte');

        return match ($driverName) {
            'fonnte' => new FonnteGateway(),
            default => throw new InvalidArgumentException("WhatsApp gateway driver [{$driverName}] not supported."),
        };
    }
}
