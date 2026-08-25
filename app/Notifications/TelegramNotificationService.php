<?php

namespace App\Notifications;

use App\Models\Bot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class TelegramNotificationService
{
    public Notes $notes;

    protected int $limit = 4000;

    public function __construct(
        public Bot $bot,
        public string $chatId,
        public string $message,
        public array $keyboard,
        public ?string $mode = 'Markdown'
    ) {}

    public function send(): array
    {
        $chatId = preg_replace('/[\/|#:].*/', '', $this->chatId);
        $messageThreadId = preg_replace('/^[^\/|#:]*[\/|#:]/', '', $this->chatId);
        $messageThreadId = $messageThreadId === $chatId ? null : $messageThreadId;

        $chunks = $this->splitMessage($this->message);
        $messageIds = [];

        foreach ($chunks as $index => $chunk) {
            $isLast = $index === array_key_last($chunks);

            $messageIds[] = $this->bot->type === 'common'
                ? $this->sendCommonBot($chatId, $messageThreadId, $chunk, $isLast)
                : $this->sendOwnBot($chatId, $messageThreadId, $chunk, $isLast, $this->bot->token);
        }
        return $messageIds;
    }

    private function sendCommonBot(string $chatId, ?string $messageThreadId, string $text, bool $isLast, int $attempt = 0): string
    {
        try {
            $response = Telegram::bot()->sendMessage([
                'chat_id' => $chatId,
                'message_thread_id' => $messageThreadId,
                'text' => $text,
                'reply_markup' => $isLast && ($keyboard = $this->buildKeyboard()) ? Keyboard::make($keyboard) : null,
                'parse_mode' => $this->mode,
            ]);

            return (string) $response->getMessageId();
        } catch (Throwable $e) {
            if ($attempt === 0 && !is_null($this->mode) && Str::contains($e->getMessage(), "Bad Request: can't parse entities")) {
                logger()->warning('В сообщении присутствуют ошибки разметки, повторяем без форматирования');
                $this->mode = null;
                return $this->sendCommonBot($chatId, $messageThreadId, $text, $isLast, $attempt + 1);
            }
            throw $e;
        }
    }

    private function sendOwnBot(string $chatId, ?string $messageThreadId, string $text, bool $isLast, ?string $token, int $attempt = 0): string
    {
        if (empty($token)) {
            throw new \RuntimeException('У бота не задан токен');
        }

        $keyboard = $isLast ? $this->buildKeyboard() : null;

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'message_thread_id' => $messageThreadId,
            'text' => $text,
            'reply_markup' => $keyboard ? json_encode($keyboard) : null,
            'parse_mode' => $this->mode,
        ]);

        if ($response->failed()) {
            $errorDescription = (string) $response->json('description');

            if ($attempt === 0 && !is_null($this->mode) && Str::contains($errorDescription, "Bad Request: can't parse entities")) {
                logger()->warning('В сообщении присутствуют ошибки разметки, повторяем без форматирования');
                $this->mode = null;
                return $this->sendOwnBot($chatId, $messageThreadId, $text, $isLast, $token, $attempt + 1);
            }

            logger()->error('Ошибка отправки уведомления для своего бота', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_id' => $chatId,
            ]);

            throw new \RuntimeException("Telegram API: {$errorDescription}");
        }

        return (string) $response->json('result.message_id');
    }

    public function editKeyboard(string $chatId, string $messageId, array $buttons): void
    {
        $chatId = preg_replace('/[\/|#:].*/', '', $chatId);
        $keyboard = $this->buildKeyboard($buttons);

        Telegram::bot()->editMessageReplyMarkup([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => $keyboard ? Keyboard::make($keyboard) : Keyboard::make(['inline_keyboard' => []]),
        ]);
    }

    private function splitMessage(string $text): array
    {
        if (mb_strlen($text) <= $this->limit) {
            return [$text];
        }

        return mb_str_split($text, $this->limit);
    }


    private function buildKeyboard(?array $buttons = null): ?array
    {
        $buttons ??= $this->keyboard;

        if (empty($buttons)) {
            return null;
        }

        $rows = collect($buttons)
            ->chunk(2)
            ->map(fn ($chunk) => $chunk
                ->map(fn ($button) => $this->buildButton($button))
                ->values()
                ->all()
            )
            ->values()
            ->all();

        return ['inline_keyboard' => $rows];
    }

    private function buildButton(array $button): array
    {
        if ($url = data_get($button, 'url')) {
            return [
                'text' => data_get($button, 'label'),
                'url' => $url,
            ];
        }

        return [
            'text' => data_get($button, 'label'),
            'callback_data' => data_get($button, 'callback_data'),
        ];
    }
}
