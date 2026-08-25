<?php

namespace App\Services;

use App\Console\Commands\CommandReplySender;
use App\DTO\UpdateDTO;
use App\Models\AuthBag;
use App\Models\TriggerButtonLog;
use Throwable;
use App\Models\AttemptLog;
use App\Models\Bot;

class SalesbotService
{
    private const ENTITY_TYPE_LEAD = 2;

    public function __construct(
        private IdempotencyService $idempotency,
        private CommandReplySender $sender,
        private ErrorCatalog $error,
        private CallbackAnswerService $callbackAnswer,
    ) {}

    public function handle(UpdateDTO $update, Bot $bot): void
    {
        if ($update->callbackId) {
            $this->callbackAnswer->answer($bot, $update->callbackId);
        }

        if (!$this->idempotency->lock($update->triggerButtonLogId)) {
            return;
        }

        $log = TriggerButtonLog::with('notificationLog')->find($update->triggerButtonLogId);
        if (!$log || !$log->notificationLog) {
            return;
        }
        $notification = $log->notificationLog;

        $authBag = AuthBag::where('account_id', $notification->account_id)->first();

        if (!$authBag) {
            logger()->error('Не найдены credentials для запуска salesbot', [
                'account_id' => $notification->account_id,
                'notification_id' => $notification->id,
            ]);
            $this->writeAttemptLog($notification->id, 'failed', $update->senderId, 'AuthBag не найден для account_id');
            return;
        }
        try {
            $authBag->auth();
            $notes = new Notes(KAuth::getApiClient());
            KAuth::getApiClient()->getRequest()->post('api/v2/salesbot/run', [
                [
                    'bot_id' => $log->salesbot_id,
                    'entity_id' => $notification->lead_id,
                    'entity_type' => self::ENTITY_TYPE_LEAD,
                ],
            ]);

            $this->writeAttemptLog($notification->id, 'success', $update->senderId);
            $notes->addToLead(
                $notification->lead_id,
                "Из {$notification->platform} запущен salesbot «{$log->label}» (кнопка «{$log->label}»)"
            );
            if ($bot) {
                $this->sender->reply($bot, $notification->chat_id, 'Сценарий запущен');
            }

            app(TriggerButtonActionService::class)->apply($log, $notification);
        } catch (Throwable $e) {
            report($e);
            $errorText = $this->error->reason($e);
            $this->writeAttemptLog($notification->id, 'failed', $update->senderId, $e->getMessage());

            if ($bot) {
                $this->sender->reply($bot, $notification->chat_id, $errorText);
            }

            try {
                (new Notes(KAuth::getApiClient()))->addToLead(
                    $notification->lead_id,
                    "Не смогли активировать сценарий по кнопке. {$e->getMessage()}"
                );
            } catch (Throwable $noteError) {
                report($noteError);
                logger()->error('Не удалось записать примечание об ошибке salesbot', [
                    'notification_id' => $notification->id,
                    'error' => $noteError->getMessage(),
                ]);
            }
        }
    }

    private function writeAttemptLog(int $notificationId, string $status, ?string $senderName, ?string $error = null): void
    {
        AttemptLog::create([
            'notification_id' => $notificationId,
            'event_type' => 'button_click',
            'status' => $status,
            'text_attempts' => $senderName,
            'details_attempts' => $error,
        ]);
    }
}
