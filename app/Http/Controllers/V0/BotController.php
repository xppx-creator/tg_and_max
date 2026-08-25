<?php

namespace App\Http\Controllers\V0;

use App\Enums\PlatformEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BotRequest;
use App\Models\Account;
use App\Models\AccountBot;
use App\Models\Bot;
use App\Telegram\TelegramWebhookRegister;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

class BotController extends Controller
{
    private const MAX_BOTS_PER_ACCOUNT = 10;

    public function __construct(
        private TelegramWebhookRegister $telegramWebhook,
    ) {
    }

    public function showList()
    {
        $account = $this->resolveAccount();

        $bots = Bot::where('is_active', 1)
            ->where('account_id', $account->id)
            ->get();

        if ($bots->isEmpty()) {
            throw new \Exception('Не найдено ни одного бота');
        }
        return $bots;
    }
    public function create(BotRequest $request)
    {
        $data = $request->all();
        $token = data_get($data, 'token');
        $messenger = data_get($data, 'platform');
        $botType = data_get($data, 'bot_type');

        $account = $this->resolveAccount();

        $result = $this->fetchBotIdentity($messenger, $token);

        if ($result === null) {
            return response()->json([
                'message' => 'Не удалось подключить бота: токен недействителен',
            ], 422);
        }

        $externalBotId = data_get($result, 'id');

        $existingBot = Bot::where('platform', $messenger)->where('bot_id', $externalBotId)->first();

        if ($existingBot) {
            if ($existingBot->type !== $botType) {
                return response()->json([
                    'message' => 'Этот бот уже подключён с другим типом (свой/общий) — тип бота нельзя изменить после создания',
                ], 422);
            }
            AccountBot::firstOrCreate([
                'account_id' => $account->id,
                'bot_id' => $existingBot->id,
            ]);

            return response()->json($existingBot);
        }

        $ownBotToken = Bot::where('account_id', $account->id)->where('token', $token)->first();

        if (!$ownBotToken && Bot::where('account_id', $account->id)->count() >= self::MAX_BOTS_PER_ACCOUNT) {
            return response()->json([
                'message' => 'Достигнут лимит: не более ' . self::MAX_BOTS_PER_ACCOUNT . ' ботов на аккаунт',
            ], 422);
        }

        if (!$ownBotToken && $botType === 'common' && Bot::where('account_id', $account->id)
                ->where('platform', $messenger)
                ->where('type', 'common')
                ->exists()) {
            return response()->json([
                'message' => 'В этом канале уже подключён общий бот — можно добавить только один',
            ], 422);
        }

        $bot = Bot::updateOrCreate(
            ['account_id' => $account->id, 'token' => $token],
            [
                'bot_id' => $externalBotId,
                'name' => data_get($result, 'first_name'),
                'username' => data_get($result, 'username'),
                'type' => $botType,
                'platform' => $messenger,
                'avatar_url' => data_get($result, 'avatar_url'),
                'welcome_message' => data_get($data, 'welcome_message'),
                'secret_token' => $ownBotToken->secret_token ?? Str::random(48),
                'is_active' => true,
            ]
        );

        AccountBot::firstOrCreate([
            'account_id' => $account->id,
            'bot_id' => $bot->id,
        ]);

        if ($messenger === 'telegram') {
            $this->telegramWebhook->register($bot);
        }

        return response()->json($bot);
    }

    private function fetchBotIdentity(string $messenger, string $token): ?array
    {
        if ($messenger === 'telegram') {
            $response = Http::post('https://api.telegram.org/bot' . $token . '/getMe', []);

            if ($response->failed() || data_get($response, 'ok') !== true) {
                return null;
            }

            return data_get($response, 'result');
        }

        if ($messenger === 'max') {
            $response = Http::withHeaders(['Authorization' => $token])->get('https://platform-api2.max.ru/me');

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        }

        throw new \Exception('Некорректный тип бота');
    }

    public function showBot(Bot $bot)
    {
        $account = $this->resolveAccount();

        abort_if($bot->account_id !== $account->id, 403, 'Бот принадлежит другому аккаунту');

        return response()->json([
            'bot_id' => $bot->bot_id,
            'name' => $bot->name,
            'username' => $bot->username,
            'type' => $bot->type,
            'platform' => $bot->platform,
            'avatar_url' => $bot->avatar_url,
        ]);
    }

    public function delete(Bot $bot)
    {
        $account = $this->resolveAccount();

        abort_if($bot->account_id !== $account->id, 403, 'Бот принадлежит другому аккаунту');

        if ($bot->platform === PlatformEnum::TELEGRAM) {
            try {
                Telegram::bot()->removeWebhook();
            } catch (\Throwable $e) {
                report($e);
                logger()->error('Не удалось снять Telegram webhook при удалении бота', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AccountBot::where('bot_id', $bot->id)->delete();

        $bot->update(['is_active' => false]);

        return response()->json(['message' => 'Бот удалён']);
    }

    private function resolveAccount(): Account
    {
        $amocrmId = KAuth::getAccount()->getAmocrmId();

        return Account::where('amocrm_id', $amocrmId)->firstOrFail();
    }
}
