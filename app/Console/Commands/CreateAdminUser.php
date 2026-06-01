<?php

namespace App\Console\Commands;

use App\Models\TelegraphBot;
use App\Models\TelegraphChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'app:make-user
        {email : Email address}
        {password : Password}
        {--name=Admin : Display name}
        {--chat= : Existing telegraph chat_id (optional, links bot account to web account)}';

    protected $description = 'Create or update an admin web/mobile user (linked to telegraph_chats).';

    public function handle(): int
    {
        $email    = $this->argument('email');
        $password = $this->argument('password');
        $name     = $this->option('name');
        $chatId   = $this->option('chat');

        // Make sure we have at least one telegraph_bot row, since chat needs FK
        $bot = TelegraphBot::query()->first();
        if (!$bot) {
            $this->error('Avval telegraph_bots jadvalida hech bo\'lmasa bitta bot bo\'lishi kerak. /artisan tinker bilan yarating yoki TELEGRAM_BOT_TOKEN va php artisan telegraph:new-bot ni ishlating.');
            return self::FAILURE;
        }

        // Find by chat_id if provided, otherwise by email
        $query = TelegraphChat::query();
        if ($chatId) {
            $chat = $query->firstOrCreate(
                ['chat_id' => $chatId, 'telegraph_bot_id' => $bot->id],
                ['name' => $name, 'is_admin' => true]
            );
        } else {
            $chat = $query->where('email', $email)->first();
            if (!$chat) {
                // Use a synthetic chat_id (won't conflict with real Telegram IDs since we use a prefix)
                $syntheticChatId = 'web-' . substr(md5($email), 0, 12);
                $chat = TelegraphChat::query()->create([
                    'chat_id'          => $syntheticChatId,
                    'telegraph_bot_id' => $bot->id,
                    'name'             => $name,
                    'is_admin'         => true,
                ]);
            }
        }

        $chat->email    = $email;
        $chat->name     = $name ?: $chat->name;
        $chat->password = Hash::make($password);
        $chat->is_admin = true;
        $chat->save();

        $this->info("✅ Admin tayyor:");
        $this->line("   ID:       {$chat->id}");
        $this->line("   Email:    {$chat->email}");
        $this->line("   Name:     {$chat->name}");
        $this->line("   chat_id:  {$chat->chat_id}");

        return self::SUCCESS;
    }
}
