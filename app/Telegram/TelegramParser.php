<?php

namespace App\Telegram;

use App\DTO\UpdateDTO;
use App\Enums\UpdateTypeEnum;
use Illuminate\Support\Arr;

class TelegramParser
{
    public function parse(array $rawUpdate): ?UpdateDTO
    {
        if (isset($rawUpdate['callback_query'])) {
            return $this->parseCallbackQuery($rawUpdate['callback_query']);
        }

        if (isset($rawUpdate['message'])) {
            return $this->parseMessage($rawUpdate['message']);
        }

        logger()->debug('Telegram: неподдерживаемый тип апдейта', ['payload' => $rawUpdate]);
        return null;
    }

    private function parseCallbackQuery(array $callbackQuery): ?UpdateDTO
    {
        $message = Arr::get($callbackQuery, 'message', []);
        $data = (string) Arr::get($callbackQuery, 'data', '');

        [$prefix, $id] = array_pad(explode(':', $data, 2), 2, null);

        if ($prefix !== 'salesbot_id' || !is_numeric($id)) {
            logger()->debug('Telegram: callback_data неожиданного формата', ['data' => $data]);
            return null;
        }

        return new UpdateDTO(
            type: UpdateTypeEnum::CALLBACK_QUERY,
            chatId: (string) Arr::get($message, 'chat.id'),
            externalMessageId: (string) Arr::get($message, 'message_id'),
            triggerButtonLogId: (int) $id,
            command: null,
            commandArgs: [],
            senderId: (int) Arr::get($callbackQuery, 'from.id'),
            isGroup: $this->resolveIsGroup(Arr::get($message, 'chat.type')),
            callbackId: (string) Arr::get($callbackQuery, 'id'),
        );
    }

    private function parseMessage(array $message): ?UpdateDTO
    {
        $text = (string) Arr::get($message, 'text', '');
        $isCommand = Arr::get($message, 'entities.0.type') === 'bot_command';

        [$command, $args] = $isCommand
            ? $this->parseCommand($text)
            : [null, []];

        return new UpdateDTO(
            type: UpdateTypeEnum::MESSAGE,
            chatId: (string) Arr::get($message, 'chat.id'),
            externalMessageId: (string) Arr::get($message, 'message_id'),
            triggerButtonLogId: null,
            command: $command,
            commandArgs: $args,
            senderId: (int) Arr::get($message, 'from.id'),
            isGroup: $this->resolveIsGroup(Arr::get($message, 'chat.type')),
        );
    }

    private function parseCommand(string $text): array
    {
        $parts = preg_split('/\s+/', trim($text));
        $raw = ltrim(array_shift($parts) ?? '', '/');
        $command = explode('@', $raw)[0];

        return [$command, array_values(array_filter($parts, fn ($p) => $p !== ''))];
    }

    private function resolveIsGroup(?string $chatType): bool
    {
        return in_array($chatType, ['group', 'supergroup'], true);
    }
}
