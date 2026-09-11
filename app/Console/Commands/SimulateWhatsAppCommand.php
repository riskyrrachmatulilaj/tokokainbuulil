<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppBotService;
use Illuminate\Console\Command;

class SimulateWhatsAppCommand extends Command
{
    protected $signature = 'wa:simulate {message : Pesan yang dikirimkan} {--sender=08123456789 : Nomor pengirim WhatsApp}';

    protected $description = 'Simulasi pesan WhatsApp masuk dan uji balasan chatbot';

    public function handle(WhatsAppBotService $botService): int
    {
        $sender = (string) $this->option('sender');
        $message = (string) $this->argument('message');

        $this->info("Pengirim : {$sender}");
        $this->info("Pesan    : {$message}");
        $this->line(str_repeat('-', 40));

        $reply = $botService->handleIncoming($sender, $message);

        $this->comment("Balasan Bot:");
        $this->line($reply);
        $this->line(str_repeat('-', 40));

        return self::SUCCESS;
    }
}
