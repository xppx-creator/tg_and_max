<?php

namespace App\Notifications;

use App\Models\Bot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MaxNotificationService
{
    protected int $limit = 4000;

    public function __construct(
        public Bot $bot,
        public string $chatId,
        public string $message,
        public array $keyboard = [],
        public string $chatType = 'group',
        public ?string $format = null,
    ) {}

    public function send(): array
    {
        $token = $this->resolveToken();
        $chunks = $this->splitMessage($this->message);
        $messageIds = [];

        foreach ($chunks as $index => $chunk) {
            $isLast = $index === array_key_last($chunks);
            $messageIds[] = $this->sendMessage($token, $chunk, $isLast);
        }

        return $messageIds;
    }

    private function sendMessage(string $token, string $text, bool $isLast): string
    {
        $payload = ['text' => $text];

        if ($format = $this->resolveMaxFormat()) {
            $payload['format'] = $format;
        }

        if ($isLast && $attachment = $this->buildKeyboardAttachment()) {
            $payload['attachments'] = [$attachment];
        }
        logger()->debug('BEBUG', [
            'payload' => $payload,
        ]);
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])
            ->withOptions(['verify' => false])
            ->withQueryParameters($this->recipientQuery())
            ->post('https://platform-api2.max.ru/messages', $payload);

        if ($response->failed()) {
            logger()->error('MAX sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_id' => $this->chatId,
            ]);

            throw new \RuntimeException("MAX API: {$response->body()}");
        }
        return (string) $response->json('message.body.mid');
    }

    public function editKeyboard(string $chatId, string $messageId, array $buttons): void
    {
        $token = $this->resolveToken();

        $payload = [];
        if ($attachment = $this->buildKeyboardAttachment($buttons)) {
            $payload['attachments'] = [$attachment];
        } else {
            $payload['attachments'] = [];
        }

        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
        ])
            ->withOptions(['verify' => false])
            ->withQueryParameters(['message_id' => $messageId])
            ->put('https://platform-api2.max.ru/messages', $payload);

        if ($response->failed() || $response->json('success') === false) {
            logger()->error('MAX editKeyboard failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        }
    }

    private function recipientQuery(): array
    {
        return $this->chatType === 'private'
            ? ['user_id' => $this->chatId]
            : ['chat_id' => $this->chatId];
    }

    private function resolveToken(): string
    {
        $token = $this->bot->type === 'common'
            ? config('max.bots.mybot.token')
            : $this->bot->token;

        if (empty($token)) {
            throw new \RuntimeException('У бота не задан токен');
        }

        return $token;
    }

    private function splitMessage(string $text): array
    {
        if (mb_strlen($text) <= $this->limit) {
            return [$text];
        }

        return mb_str_split($text, $this->limit);
    }

    protected function buildKeyboardAttachment(?array $buttons = null): ?array
    {
        $buttons = collect($buttons ?? $this->keyboard);

        if ($buttons->isEmpty()) {
            return null;
        }

        $rows = $buttons
            ->chunk(2)
            ->map(fn (Collection $chunk) => $chunk
                ->map(fn ($button) => $this->buildButton($button))
                ->values()
                ->all()
            )
            ->values()
            ->all();

        return [
            'type' => 'inline_keyboard',
            'payload' => [
                'buttons' => $rows,
            ],
        ];
    }

    protected function buildButton(array $button): array
    {
        if ($url = data_get($button, 'url')) {
            return [
                'type' => 'link',
                'text' => data_get($button, 'label'),
                'url' => $url,
            ];
        }

        return [
            'type' => 'callback',
            'text' => data_get($button, 'label'),
            'payload' => data_get($button, 'callback_data'),
        ];
    }
    private function resolveMaxFormat(): ?string
    {
        return match ($this->format) {
            'Markdown', 'MarkdownV2' => 'markdown',
            'HTML', 'Html' => 'html',
            default => null,
        };
    }
}
