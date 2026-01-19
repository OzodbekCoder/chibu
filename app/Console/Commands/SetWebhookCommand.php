<?php

namespace App\Console\Commands;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Webhookni sozlash';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = env('TELEGRAM_NAME');
        $token = env('TELEGRAM_TOKEN');

        if (empty($name) || empty($token)) {
            $this->error(empty($name) ? 'Bot nomi topilmadi!' : 'Bot tokeni topilmadi!');
            return;
        }

        $bot = TelegraphBot::firstOrCreate(
            ['token' => $token, 'name' => $name]
        );

        $bot->registerWebhook()->send();
        $this->info('Webhook muvaffaqiyatli o\'rnatildi!');
    }
}
