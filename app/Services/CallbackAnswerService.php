<?php

namespace App\Services;

use App\Enums\PlatformEnum;
use App\Models\Bot;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

class CallbackAnswerService
{
    public function answer(Bot $bot, string $callbackId): void
    {
        if ($callbackId === '') {
            return;
        }

        $platform = $bot->platform instanceof PlatformEnum ? $bot->platform->value : (string) $bot->platform;

        match ($platform) {
            PlatformEnum::TELEGRAM->value => $this->answerTelegram($bot, $callbackId),
            PlatformEnum::MAX->value => $this->answerMax($bot, $callbackId),
            default => logger()->warning('CallbackAnswerService: неизвестная платформа бота', [
                'bot_id' => $bot->id,
                'platform' => $platform,
            ]),
        };
    }

    private function answerTelegram(Bot $bot, string $callbackQueryId): void
    {
        if ($bot->type === 'common') {
            Telegram::bot()->answerCallbackQuery(['callback_query_id' => $callbackQueryId]);
            return;
        }

        if (empty($bot->token)) {
            logger()->error('answerCallbackQuery: у бота не задан токен', ['bot_id' => $bot->id]);
            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$bot->token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
        ]);

        if ($response->failed()) {
            logger()->error('Telegram answerCallbackQuery failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'bot_id' => $bot->id,
            ]);
        }
    }

    private function answerMax(Bot $bot, string $callbackId): void
    {
        $token = $bot->type === 'common' ? config('max.bots.mybot.token') : $bot->token;

        if (empty($token)) {
            logger()->error('POST /answers: у бота не задан токен', ['bot_id' => $bot->id]);
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])
            ->withOptions(['verify' => false])
            ->withQueryParameters(['callback_id' => $callbackId])
            ->post('https://platform-api2.max.ru/answers', []);

        if ($response->failed() || $response->json('success') === false) {
            logger()->error('MAX POST /answers failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'callback_id' => $callbackId,
                'bot_id' => $bot->id,
            ]);
        }
    }
}
