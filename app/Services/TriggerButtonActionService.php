<?php

namespace App\Services;

use App\Enums\ActionAfterEnum;
use App\Enums\PlatformEnum;
use App\Models\NotificationLog;
use App\Models\TriggerButtonLog;
use App\Notifications\MaxNotificationService;
use App\Notifications\TelegramNotificationService;

class TriggerButtonActionService
{
    public function apply(TriggerButtonLog $clickedButton, NotificationLog $notification): void
    {
        $messageIds = $notification->message_ids ?? [];
        $lastMessageId = end($messageIds);

        if (!$lastMessageId) {
            return;
        }

        $buttons = match ($clickedButton->action_after) {
            ActionAfterEnum::DELETE_ALL_BUTTONS->value => [],
            ActionAfterEnum::DELETE_ONE_BUTTON->value => $this->remainingButtons($notification, $clickedButton),
            default => null,
        };

        if ($buttons === null) {
            return;
        }

        $sender = $this->resolveSender($notification);
        $sender->editKeyboard($notification->chat_id, $lastMessageId, $buttons);
    }

    private function resolveSender(NotificationLog $notification): TelegramNotificationService|MaxNotificationService
    {
        return match ($notification->platform) {
            PlatformEnum::TELEGRAM->value => new TelegramNotificationService($notification->bot, $notification->chat_id, '', []),
            PlatformEnum::MAX->value => new MaxNotificationService($notification->bot, $notification->chat_id, '', []),
        };
    }

    private function remainingButtons(NotificationLog $notification, TriggerButtonLog $clicked): array
    {
        return $notification->triggerButtonLogs()
            ->where('id', '!=', $clicked->id)
            ->orderBy('sort')
            ->get()
            ->map(fn ($btn) => [
                'label' => $btn->label,
                'callback_data' => $btn->callback_data ?? "salesbot_id:{$btn->id}",
                'url' => $btn->button_type === 'url' ? $btn->url_button : null,
            ])
            ->values()
            ->all();
    }
}
