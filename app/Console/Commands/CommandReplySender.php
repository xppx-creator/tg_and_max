<?php

namespace App\Console\Commands;

use App\Enums\ChatTypeEnum;
use App\Enums\PlatformEnum;
use App\Models\Bot;
use App\Models\Chat;
use App\Notifications\MaxNotificationService;
use App\Notifications\TelegramNotificationService;


class CommandReplySender
{
    public function reply(Bot $bot, string $chatId, string $text): void
    {
        $sender = match ($bot->platform) {
            PlatformEnum::TELEGRAM => new TelegramNotificationService($bot, $chatId, $text, []),
            PlatformEnum::MAX => new MaxNotificationService($bot, $chatId, $text, [], $this->resolveChatType($bot, $chatId)->value),
        };
        $sender->send();
    }

    private function resolveChatType(Bot $bot, string $chatId): ChatTypeEnum
    {
        $chat = Chat::where('bot_id', $bot->id)->where('external_id', $chatId)->first();

        return $chat?->type === ChatTypeEnum::PRIVATE_MESSAGE->value ? ChatTypeEnum::PRIVATE_MESSAGE : ChatTypeEnum::GROUP;
    }
}
