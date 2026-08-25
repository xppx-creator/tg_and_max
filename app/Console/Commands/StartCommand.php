<?php

namespace App\Console\Commands;

use App\DTO\UpdateDTO;
use App\Models\Bot;
use App\Prototypes\SettingsBag;

class StartCommand
{
    public function handle(UpdateDTO $update, Bot $bot): void
    {
        $text = $bot->welcome_message
            ?: 'Привет! Чтобы подключить бота к аккаунту amoCRM, напишите /connect {id_аккаунта}.';

        (new CommandReplySender)->reply($bot, $update->chatId, $text);
    }
}
