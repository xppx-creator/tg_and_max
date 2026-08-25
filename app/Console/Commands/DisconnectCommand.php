<?php

namespace App\Console\Commands;

use App\DTO\UpdateDTO;
use App\Models\Account;
use App\Models\AccountChat;
use App\Models\Bot;
use App\Models\Chat;

class DisconnectCommand
{
    public function handle(UpdateDTO $update, Bot $bot): void
    {
        if ($bot->type !== 'common') {
            return;
        }

        $replySender = new CommandReplySender();
        $amocrmAccountId = $update->commandArgs[0] ?? null;

        if (!$amocrmAccountId) {
            $replySender->reply($bot, $update->chatId, 'Укажите id аккаунта: /disconnect {id}');
            return;
        }

        $account = Account::where('amocrm_id', $amocrmAccountId)->first();
        $chat = Chat::where('bot_id', $bot->id)->where('external_id', $update->chatId)->first();

        if (!$account || !$chat) {
            $replySender->reply($bot, $update->chatId, 'Аккаунт не был подключён');
            return;
        }

        $disconnected = $this->disconnect($chat, $account->id);

        $replySender->reply($bot, $update->chatId, $disconnected
            ? 'Аккаунт amoCRM ' . $amocrmAccountId . ' отключён.'
            : 'Аккаунт amoCRM ' . $amocrmAccountId . ' не был подключён.');
    }

    private function disconnect(Chat $chat, int $accountId): bool
    {
        $deleted = AccountChat::where('account_id', $accountId)
            ->where('chat_id', $chat->id)
            ->delete();

        return $deleted > 0;
    }
}
