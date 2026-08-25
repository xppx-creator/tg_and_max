<?php

namespace App\Telegram;

use App\Models\Bot;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWebhookRegister
{
    public function register(Bot $bot): void
    {
        $url = route('webhooks.tg', ['bot' => $bot->id]);

        Telegram::bot()->setWebhook([
            'url' => $url,
            'secret_token' => $bot->secret_token,
        ]);
    }
}
