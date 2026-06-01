<?php

namespace App\Telegraph\Traits;

use App\Models\Client;
use App\Models\TelegraphChat;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;

trait HandlesClientCreation
{
    public function createShipmentNewClient(): void
    {
        $botUser = $this->getOrCreateBotUser();
        $payload = $botUser->payload ?? [];

        $this->setState($botUser, 'client.create.name', $payload);
        $this->chat->html("👤 <b>Yangi mijoz</b>\n\nMijoz ismini yuboring:")->send();
    }

    protected function stepClientName(TelegraphChat $botUser, string $text): void
    {
        $name = trim($text);
        if (mb_strlen($name) < 2) {
            $this->chat->html("❌ Ism juda qisqa. Qaytadan yuboring.")->send();
            return;
        }

        $payload                      = $botUser->payload ?? [];
        $payload['new_client']['name'] = $name;

        $this->setState($botUser, 'client.create.phone', $payload);

        $this->chat->html("📞 Telefon raqamini ulashing yoki o'tkazib yuboring:")
            ->replyKeyboard(
                ReplyKeyboard::make()
                    ->oneTime()
                    ->resize()
                    ->row([ReplyButton::make("📱 Raqamni ulashish")->requestContact()])
                    ->row([ReplyButton::make("O'tkazib yuborish")])
            )
            ->send();
    }

    protected function stepClientPhone(TelegraphChat $botUser, string $text): void
    {
        $phone = trim($text) === "O'tkazib yuborish" ? null : trim($text);
        $this->finishClientCreation($botUser, $phone ?: null);
    }

    protected function finishClientCreation(TelegraphChat $botUser, ?string $phone): void
    {
        $payload    = $botUser->payload ?? [];
        $clientData = $payload['new_client'] ?? [];

        if (empty($clientData['name'])) {
            $this->startCreateShipment();
            return;
        }

        $client = Client::query()->create([
            'name'          => $clientData['name'],
            'phone'         => $phone,
            'created_by_id' => $botUser->id,
        ]);

        unset($payload['new_client']);
        $payload['shipment']['client_id'] = $client->id;

        $this->chat->html("✅ Mijoz saqlandi: <b>{$client->name}</b>")
            ->removeReplyKeyboard()
            ->send();

        $this->askForNote($botUser, $payload);
    }
}
