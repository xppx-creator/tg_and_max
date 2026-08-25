<?php

namespace App\DTO;

use App\Enums\UpdateTypeEnum;
class UpdateDTO
{
    public function __construct(
        public readonly UpdateTypeEnum $type,
        public readonly ?string $chatId,
        public readonly ?string $externalMessageId,
        public readonly ?int $triggerButtonLogId,
        public readonly ?string $command,
        public readonly array $commandArgs,
        public readonly ?int $senderId,
        public readonly ?bool $isGroup,
        public readonly ?string $callbackId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'chat_id' => $this->chatId,
            'external_message' => $this->externalMessageId,
            'button_log_id' => $this->triggerButtonLogId,
            'command' => $this->command,
            'command_args' => $this->command,
            'sender_id' => $this->senderId,
            'is_group' => $this->isGroup,
            'callback_id' => $this->callbackId,
        ];
    }
}
