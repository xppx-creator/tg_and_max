<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Enums\PlatformEnum;

class NoteTextBuilder
{
    public function success(NotificationLog $notification): string
    {
        return sprintf(
            'Отправлено уведомление в %s (%s): %s',
            $this->channelLabel($notification),
            $notification->bot_label,
            $notification->message
        );
    }

    public function successWithFieldWriteFailed(NotificationLog $notification, string $fieldName, string $fieldError): string
    {
        return sprintf(
            'Отправлено уведомление в %s (%s): %s. Не удалось записать ID сообщения в поле «%s»: %s',
            $this->channelLabel($notification),
            $notification->bot_label,
            $notification->message,
            $fieldName,
            $fieldError
        );
    }

    public function error(NotificationLog $notification): string
    {
        return sprintf(
            'Ошибка отправки уведомления в %s (%s): %s',
            $this->channelLabel($notification),
            $notification->bot_label,
            $notification->error_message
        );
    }

    private function channelLabel(NotificationLog $notification): string
    {
        return match ($notification->platform) {
            PlatformEnum::MAX->value => 'MAX',
            PlatformEnum::TELEGRAM->value => 'Telegram',
        };
    }
}
