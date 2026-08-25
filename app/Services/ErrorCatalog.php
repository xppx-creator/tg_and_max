<?php

namespace App\Services;

use Illuminate\Support\Str;
use Throwable;

class ErrorCatalog
{

    private const PERMANENT_MARKERS = [
        'bot was blocked',
        'user is deactivated',
        'chat not found',
        'CHAT_ID_INVALID',
        'bot was kicked',
        'have no rights to send a message',
    ];

    public function isPermanent(Throwable $e): bool
    {
        return Str::contains($e->getMessage(), self::PERMANENT_MARKERS, ignoreCase: true);
    }

    public function reason(Throwable $e): string
    {
        $message = $e->getMessage();

        return match (true) {
            Str::contains($message, 'bot was blocked', true) => 'Пользователь заблокировал бота',
            Str::contains($message, 'user is deactivated', true) => 'Аккаунт пользователя удалён',
            Str::contains($message, ['chat not found', 'CHAT_ID_INVALID'], true) => 'Чат не найден - возможно, был удалён',
            Str::contains($message, 'bot was kicked', true) => 'Бот удалён из чата/группы',
            Str::contains($message, 'have no rights', true) => 'У бота нет прав отправлять сообщения в этот чат',
            Str::contains($message, ['429', 'Too Many Requests'], true) => 'Превышен лимит запросов площадки, повторим попытку',
            default => 'Не удалось отправить сообщение',
        };
    }
}
