<?php

namespace App\Console\Commands;

use App\Enums\PlatformEnum;
use App\Models\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


class CheckConnectWebhookCommand extends Command
{
    protected $signature = 'bots:check-webhook';
    protected $description = 'Проверка подписки на вебхук у активных ботов, обновление last_ping_at';

    public function handle(): int
    {
        Bot::where('platform', PlatformEnum::MAX)
            ->where('is_active', true)
            ->each(function (Bot $bot) {
                $token = $bot->type === 'common' ? config('max.bots.mybot.token') : $bot->token;

                if (empty($token)) {
                    return;
                }

                $response = Http::withHeaders(['Authorization' => $token])
                    ->withOptions(['verify' => false])
                    ->get('https://platform-api2.max.ru/subscriptions');

                $hasActiveSubscription = $response->successful()
                    && collect($response->json('subscriptions', []))->isNotEmpty();

                if ($hasActiveSubscription) {
                    $bot->update(['last_ping_at' => now()]);
                } else {
                    logger()->warning('MAX: подписка на вебхук не найдена', ['bot_id' => $bot->id]);
                }
            });

        return self::SUCCESS;
    }
}
