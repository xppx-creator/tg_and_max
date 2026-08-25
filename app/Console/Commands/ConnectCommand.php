<?php

namespace App\Console\Commands;

use App\DTO\UpdateDTO;
use App\Enums\ChatTypeEnum;
use App\Models\Account;
use App\Models\AccountBot;
use App\Models\AccountChat;
use App\Models\Bot;
use App\Models\Chat;

class ConnectCommand
{
    public function handle(UpdateDTO $update, Bot $bot): void
    {

        if ($bot->type !== 'common') {
            return;
        }

        $replySender = new CommandReplySender();
        $amocrmAccountId = $update->commandArgs[0] ?? null;

        if (!$amocrmAccountId) {
            $replySender->reply($bot, $update->chatId, 'Укажите id аккаунта: /connect {id}');
            return;
        }

        $account = Account::where('amocrm_id', $amocrmAccountId)->where('is_active', true)->first();

        if (!$account) {
            $replySender->reply($bot, $update->chatId, 'Не удалось подключить аккаунт. Проверьте ID аккаунта amoCRM и повторите: /connect {id}');
            return;
        }
        $accountBots = AccountBot::where('account_id', $account->id)->where('bot_id', $bot->id)->first();

        if (!$accountBots) {
            $replySender->reply($bot, $update->chatId, 'Не удалось подключить аккаунт. Проверьте ID аккаунта amoCRM и повторите: /connect {id}');
            return;
        }

        $chat = Chat::firstOrCreate(
            ['bot_id' => $bot->id, 'external_id' => $update->chatId],
            ['type' => $update->isGroup ? ChatTypeEnum::GROUP->value : ChatTypeEnum::PRIVATE_MESSAGE->value]
        );

        $created = $this->connect($chat, $account->id);

        $replySender->reply($bot, $update->chatId, $created
            ? 'Аккаунт amoCRM ' .  $amocrmAccountId . ' подключён. Уведомления из этого аккаунта могут приходить сюда.'
            : 'Аккаунт amoCRM ' .  $amocrmAccountId . ' уже подключён сюда.'
        );
    }

    private function connect(Chat $chat, int $accountId): bool
    {
        $accountChat = AccountChat::firstOrCreate([
            'account_id' => $accountId,
            'chat_id' => $chat->id,
        ]);

        return $accountChat->wasRecentlyCreated;
    }
}
