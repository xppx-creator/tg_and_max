<?php

namespace App\Jobs;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use App\Enums\PlatformEnum;
use App\Models\AttemptLog;
use App\Models\AuthBag;
use App\Models\NotificationLog;
use App\Notifications\MaxNotificationService;
use App\Notifications\TelegramNotificationService;
use App\Services\ErrorCatalog;
use App\Services\NoteTextBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use AmoCRM\Models\LeadModel;
use AmoCRM\Helpers\EntityTypesInterface;
use Makeroi\Amocrm\Services\Notes;
use Makeroi\Amocrm\Kernel\Auth\KAuth;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $notificationId,
        public AmoCRMApiClient $client,
    ) {}

    public function handle(
        ErrorCatalog $errorCatalog,
        NoteTextBuilder $noteTextBuilder
    )
    {
        $notification = NotificationLog::with('bot', 'triggerButtonLogs')->find($this->notificationId);

        if (empty($notification)) {
            logger()->info('NotificationLog не найден', ['id' => $this->notificationId]);
            return;
        }

        $authBag = AuthBag::where('account_id', $notification->account_id)->first();
        if (!$authBag) {
            logger()->error('AuthBag не найден для account_id', ['account_id' => $notification->account_id]);
            $this->writeAttemptLog($notification->id, 'failed', 'attempt', 'AuthBag не найден');
            $notification->update(['status' => 'failed', 'error_message' => 'Не найдены credentials для отправки', 'finished_at' => now()]);
            return;
        }

        $authBag->auth();
        $this->client = KAuth::getApiClient();

        $buttons = $notification->triggerButtonLogs->sortBy('sort')->map(fn ($btn) => [
            'label' => $btn->label,
            'callback_data' => $btn->callback_data ?? "salesbot_id:{$btn->id}",
            'url' => $btn->button_type === 'url' ? $btn->url_button : null,
        ])->values()->all();

        $notes = new Notes($this->client);
        try {
            $sender = match ($notification->platform) {
                PlatformEnum::TELEGRAM->value => new TelegramNotificationService($notification->bot, $notification->chat_id, $notification->message, $buttons, $notification->format_message),
                PlatformEnum::MAX->value => new MaxNotificationService($notification->bot, $notification->chat_id, $notification->message, $buttons, format: $notification->format_message),
            };

            $messageIds = $sender->send();
            logger()->debug('Сообщение было отправлено в мессенджер', ['message_ids' => $messageIds]);

            $notification->update([
                'message_ids' => $messageIds,
                'status' => 'sent',
                'finished_at' => now(),
            ]);

            $this->writeAttemptLog($notification->id, 'success', 'attempt');

            try {
                $this->writeFieldId($notification, $messageIds);
                $notes->addServiceNote(EntityTypesInterface::LEADS, $notification->lead_id, $noteTextBuilder->success($notification));
            } catch (\Throwable $e) {
                report($e);
                $this->writeAttemptLog($notification->id, 'failed', 'field_write', $e->getMessage());
                $notes->addServiceNote(
                    EntityTypesInterface::LEADS,
                    $notification->lead_id,
                    $noteTextBuilder->successWithFieldWriteFailed(
                        $notification,
                        $this->resolveFieldName($notification->field_id),
                        $e->getMessage()
                    )
                );
            }
        } catch (\Throwable $e) {
            $reason = $errorCatalog->reason($e);
            $this->writeAttemptLog($notification->id, 'failed', 'attempt', $e->getMessage());

            if ($errorCatalog->isPermanent($e)) {
                $notification->update(['status' => 'failed', 'error_message' => $reason, 'finished_at' => now()]);
                $notes->addServiceNote(EntityTypesInterface::LEADS, $notification->lead_id, $noteTextBuilder->error($notification));
                $this->fail($e);
                return;
            }

            if ($this->attempts() >= $this->tries) {
                $notification->update(['status' => 'failed', 'error_message' => $reason, 'finished_at' => now()]);
                $notes->addServiceNote(EntityTypesInterface::LEADS, $notification->lead_id, $noteTextBuilder->error($notification));
            }

            throw $e;
        }
    }

    private function writeAttemptLog(int $notificationId, string $status, string $eventType, ?string $error = null): void
    {
        AttemptLog::create([
            'notification_id' => $notificationId,
            'event_type' => $eventType,
            'status' => $status,
            'attempts_number' => $this->attempts(),
            'details_attempts' => $error,
        ]);
    }

    private function writeFieldId(NotificationLog $notification, array $messageIds): void
    {
        if (!$notification->field_id || empty($messageIds)) {
            return;
        }

        $field = (new TextCustomFieldValuesModel())
            ->setFieldId($notification->field_id)
            ->setValues(
                (new TextCustomFieldValueCollection())
                    ->add((new TextCustomFieldValueModel())->setValue((string) $messageIds[0]))
            );

        $leadModel = (new LeadModel())
            ->setId($notification->lead_id)
            ->setCustomFieldsValues((new CustomFieldsValuesCollection())->add($field));

        $this->client->leads()->updateOne($leadModel);
    }

    private function resolveFieldName(int $fieldId): string
    {
        try {
            return $this->client->customFields(EntityTypesInterface::LEADS)->getOne($fieldId)->getName();
        } catch (\Throwable $e) {
            return (string) $fieldId;
        }
    }
}
