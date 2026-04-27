<?php

namespace App\Console\Commands;

use App\Models\TelegraphChat;
use Illuminate\Console\Command;

class MakeBotAdmin extends Command
{
    protected $signature = 'bot:make-admin {chat_id}';
    protected $description = 'Telegram chat_id bo\'yicha foydalanuvchini admin qilish';

    public function handle(): int
    {
        $chatId = (string) $this->argument('chat_id');

        $user = TelegraphChat::query()->where('chat_id', $chatId)->first();

        if (!$user) {
            $this->error("chat_id={$chatId} topilmadi. Avval /start bosing.");
            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("✅ {$user->name} (chat_id={$chatId}) admin qilindi.");
        return self::SUCCESS;
    }
}
