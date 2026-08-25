<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class GroupAdminCheck
{
    public function isAdminTelegram(string $chatId, int $userId): bool
    {
        try {
            $member = Telegram::bot()->getChatMember([
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);
        } catch (Throwable $e) {
            report($e);
            return false;
        }

        return in_array($member->status, ['administrator', 'creator'], true);
    }

    public function isAdminMax(Bot $bot, string $chatId, int $userId): bool
    {
        $token = $bot->type === 'common' ? config('max.bots.mybot.token') : $bot->token;

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->withOptions(['verify' => false])
                ->get("https://platform-api2.max.ru/chats/{$chatId}/members/admins");
        } catch (Throwable $e) {
            report($e);
            return false;
        }

        if ($response->failed()) {
            logger()->error('MAX: не удалось получить список админов чата', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_id' => $chatId,
            ]);
            return false;
        }

        $admins = collect($response->json('members', []));

        return $admins->contains(fn ($admin) => (int) data_get($admin, 'user_id') === $userId);
    }
}
