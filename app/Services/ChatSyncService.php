<?php

namespace App\Services;

use App\DTO\UpdateDTO;
use App\Enums\ChatTypeEnum;
use App\Models\AccountChat;
use App\Models\Bot;
use App\Models\Chat;

class ChatSyncService
{
    public function handle(UpdateDTO $update, Bot $bot): void
    {
        if ($bot->type !== 'own' || !$bot->account_id) {
            return;
        }

        $chat = Chat::firstOrCreate(
            ['bot_id' => $bot->id, 'external_id' => $update->chatId],
            ['type' => $update->isGroup ? ChatTypeEnum::GROUP->value : ChatTypeEnum::PRIVATE_MESSAGE->value]
        );

        AccountChat::firstOrCreate([
            'account_id' => $bot->account_id,
            'chat_id' => $chat->id,
        ]);
    }
}
