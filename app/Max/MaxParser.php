<?php

namespace App\Max;

use App\DTO\UpdateDTO;
use App\Enums\UpdateTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MaxParser
{
    public function parse(array $data): ?UpdateDTO
    {
        return match (Arr::get($data, 'update_type')) {
            'message_callback' => $this->parseCallback($data),
            'message_created' => $this->parseMessage($data),
            default => null,
        };
    }

    private function parseCallback(array $data): ?UpdateDTO
    {
        $payload = (string) Arr::get($data, 'callback.payload', '');

        [$prefix, $id] = array_pad(explode(':', $payload, 2), 2, null);

        if ($prefix !== 'salesbot_id' || !is_numeric($id)) {
            logger()->debug('MAX: callback payload неожиданного формата', ['payload' => $payload]);
            return null;
        }

        return new UpdateDTO(
            type: UpdateTypeEnum::CALLBACK_QUERY,
            chatId: (string) Arr::get($data, 'message.recipient.chat_id'),
            externalMessageId: (string) Arr::get($data, 'message.body.mid'),
            triggerButtonLogId: (int) $id,
            command: null,
            commandArgs: [],
            senderId: (int) Arr::get($data, 'callback.user.user_id'),
            isGroup: Arr::get($data, 'message.recipient.chat_type') !== 'dialog',
            callbackId: (string) Arr::get($data, 'callback.callback_id'),
        );
    }

    private function parseMessage(array $data): ?UpdateDTO
    {
        $text = (string) Arr::get($data, 'message.body.text', '');
        $isCommand = Str::startsWith($text, '/');

        [$command, $args] = $isCommand
            ? $this->parseCommand($text)
            : [null, []];

        return new UpdateDTO(
            type: UpdateTypeEnum::MESSAGE,
            chatId: (string) Arr::get($data, 'message.recipient.chat_id'),
            externalMessageId: (string) Arr::get($data, 'message.body.mid'),
            triggerButtonLogId: null,
            command: $command,
            commandArgs: $args,
            senderId: (int) Arr::get($data, 'message.sender.user_id'),
            isGroup: Arr::get($data, 'message.recipient.chat_type') !== 'dialog',
        );
    }

    private function parseCommand(string $text): array
    {
        $parts = preg_split('/\s+/', trim($text));
        $command = ltrim(array_shift($parts) ?? '', '/');

        return [$command, array_values(array_filter($parts, fn ($p) => $p !== ''))];
    }
}
