<?php

namespace App\Console\Commands;

use App\DTO\UpdateDTO;
use App\Models\Bot;
use App\Models\Chat;

class AccountCommand
{
    public function handle(UpdateDTO $update, Bot $bot): void
    {
        $chat = Chat::where('external_id', $update->chatId)
            ->where('bot_id', $bot->id)
            ->first();

        $replySender = new CommandReplySender();
        if (!$chat) {
            $replySender->reply($bot, $update->chatId, 'Сюда не подключено ни одного аккаунта amoCRM. Подключите: /connect {id}');
            return;
        }

        $accountIds = $chat->accountChats()
            ->with('account')
            ->get()
            ->pluck('account.amocrm_id')
            ->filter()
            ->values()
            ->all();

        $replySender->reply(
            $bot,
            $update->chatId,
            empty($accountIds)
                ? 'Сюда не подключено ни одного аккаунта amoCRM. Подключите: /connect {id}'
                : "Подключённые аккаунты amoCRM:\n" . implode("\n", $accountIds)
        );
    }
}
