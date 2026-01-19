<?php

declare(strict_types=1);

namespace App\Telegraph;

use DefStudio\Telegraph\Handlers\WebhookHandler;

class MyWebhookHandler extends WebhookHandler
{
    public function id()
    {
        $data = $this->message->toArray();
        $id = $this->chat->chat_id;
        $this->reply(
            'Assalom alekum xurmatli ' . (
                ($data['from']['first_name'] . ' ' . $data['from']['last_name']) == ' ' ? 'mijoz' : $data['from']['first_name'] . ' ' . $data['from']['last_name'])
                . ".\r\n<b>Sizning telegram ID: $id.</b>"
        );
    }
}
