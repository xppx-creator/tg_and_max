<?php

namespace App\Notifications;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Models\LeadModel;
use App\Jobs\SendNotificationJob;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\NotificationLog;
use App\Models\TriggerButtonLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Trigger;
use App\Models\Account;

class NotificationProcessor
{
    protected Notes $notes;

    public function __construct(
        protected int $leadId,
        protected array $settingBag,
        protected int $accountId,
    ) {
        $this->notes = new Notes(KAuth::getApiClient());
    }

    public function handle(): void
    {
        $lead = $this->getSyncLead();
        if (empty($lead)) {
            $this->addNoteToLead('Ошибка при попытке получения сделки из amoCRM');
            return;
        }

        $message = $this->getMessage($lead);
        if (empty($message)) {
            $this->addNoteToLead('Текст сообщения пустой. Не можем отправить оповещение');
            return;
        }

        $bot = Bot::where('bot_id', $this->getBotId())->first();
        if (!$bot) {
            $this->addNoteToLead('Бот из настроек триггера не найден');
            return;
        }

        $chatId = $this->getChatId($lead);
        if (!$chatId) {
            $this->addNoteToLead($this->isChatFromField()
                ? 'Поле с ID чата пустое или недоступно — не можем определить, куда отправить уведомление'
                : 'ID чата не указан в настройках триггера');
            return;
        }

        $account = Account::where('amocrm_id', $this->accountId)->first();
        if (!$account) {
            logger()->error('Account не найден для amocrm_id', ['amocrm_id' => $this->accountId]);
            return;
        }

        $chat = Chat::where('external_id', $chatId)->where('bot_id', $bot->id)->first();
        if (!$chat) {
            logger()->error('Chat не найден для external_id', ['external_id' => $chatId, 'bot_id' => $bot->id]);
            $this->addNoteToLead('Чат не найден — возможно, бот ещё не добавлен в этот чат или чат был удалён');
            return;
        }

        $buttons = $this->resolveButtons($lead);
        $emptyUrlButton = $this->findEmptyUrlButton($buttons);
        if ($emptyUrlButton !== null) {
            $this->addNoteToLead(sprintf(
                'Ошибка отправки уведомления в %s (%s): Пустой URL кнопки «%s» после подстановки масок — уведомление не отправлено',
                $this->channelLabel($bot),
                $bot->name,
                $emptyUrlButton['label']
            ));

            $this->createNotificationLog($account, $bot, $chat, $message, [
                'status' => 'failed',
                'error_message' => 'Пустой URL кнопки «Ссылка» после подстановки масок',
                'finished_at' => now(),
            ]);
            return;
        }

        $notification = $this->createNotificationLog($account, $bot, $chat, $message, [
            'status' => 'pending',
        ]);

        $this->snapshotButtons($notification, $buttons);

        SendNotificationJob::dispatch($notification->id, KAuth::getApiClient());
    }
    private function createNotificationLog(Account $account, Bot $bot, Chat $chat, string $message, array $overrides): NotificationLog
    {
        $trigger = Trigger::find(Arr::get($this->settingBag, 'trigger_uuid'));

        return NotificationLog::create(array_merge([
            'account_id' => $account->id,
            'lead_id' => $this->leadId,
            'platform' => $bot->platform,
            'bot_id' => $bot->id,
            'bot_label' => $bot->name,
            'chat_id' => $chat->id,
            'chat_label' => $chat->title,
            'trigger_id' => $trigger?->id,
            'trigger_name' => $trigger?->label,
            'trigger_type' => $trigger?->source_chat,
            'message' => $message,
            'format_message' => $this->getMessageType(),
            'field_id' => Arr::get($this->settingBag, 'field_id'),
            'source_type' => Arr::get($this->settingBag, 'source_type'),
            'started_at' => now(),
        ], $overrides));
    }

    private function resolveButtons(LeadModel $lead): array
    {
        $buttons = Arr::get($this->settingBag, 'buttons', []);
        $resolved = [];

        foreach ($buttons as $index => $button) {
            $buttonType = Arr::get($button, 'button_type', 'salesbot');
            $rawUrl = Arr::get($button, 'url_button');

            $resolved[] = [
                'label' => Arr::get($button, 'label') ?? Arr::get($button, 'text'),
                'button_type' => $buttonType,
                'url_button' => $buttonType === 'url' && $rawUrl
                    ? $this->resolveButtonUrl($lead, $rawUrl)
                    : $rawUrl,
                'salesbot_id' => Arr::get($button, 'salesbot_id') ?? Arr::get($button, 'id'),
                'action_after' => Arr::get($button, 'action_after'),
                'sort' => Arr::get($button, 'sort', $index),
            ];
        }

        return $resolved;
    }

    private function resolveButtonUrl(LeadModel $lead, string $url): string
    {
        return trim(Masks::replace($url, entities: ['lead' => $lead],
            flags: Masks::FORCE_FILL_COMPANY | Masks::FORCE_FILL_CONTACT));
    }

    private function findEmptyUrlButton(array $buttons): ?array
    {
        foreach ($buttons as $button) {
            if ($button['button_type'] === 'url' && empty($button['url_button'])) {
                return $button;
            }
        }

        return null;
    }

    private function channelLabel(Bot $bot): string
    {
        return match ($bot->platform) {
            'max' => 'MAX',
            'telegram' => 'Telegram',
            default => (string) $bot->platform,
        };
    }

    private function snapshotButtons(NotificationLog $notification, array $buttons): void
    {
        foreach ($buttons as $button) {
            $log = TriggerButtonLog::create([
                'notification_log_id' => $notification->id,
                'label' => $button['label'],
                'button_type' => $button['button_type'],
                'url_button' => $button['url_button'],
                'salesbot_id' => $button['salesbot_id'],
                'action_after' => $button['action_after'],
                'sort' => $button['sort'],
            ]);

            $log->update(['callback_data' => "salesbot_id:{$log->id}"]);
        }
    }

    protected function getSyncLead(): ?LeadModel
    {
        try {
            return KAuth::getApiClient()->leads()->getOne($this->leadId, ['contacts', 'company']);
        } catch (AmoCRMMissedTokenException|AmoCRMoAuthApiException|AmoCRMApiException $e) {
            report($e);
            return null;
        }
    }

    protected function addNoteToLead(string $text): void
    {
        $this->notes->addToLead($this->leadId, $text);
    }

    protected function getMessage(LeadModel $lead): string
    {
        $message = Arr::get($this->settingBag, 'message');

        if (!$this->isEscapeFields()) {
            return Masks::replace($message, entities: ['lead' => $lead],
                flags: Masks::FORCE_FILL_COMPANY | Masks::FORCE_FILL_CONTACT);
        }

        $placeholders = Masks::extractAndFill($message, ['lead' => $lead],
            flags: Masks::FORCE_FILL_COMPANY | Masks::FORCE_FILL_CONTACT);

        return $this->escapeFields($message, $placeholders);
    }

    protected function isEscapeFields(): bool
    {
        return Arr::get($this->settingBag, 'is_escape_fields') ?? false;
    }

    private function escapeFields(string $message, PlaceholdersCollection $collection): string
    {
        foreach ($collection as $placeholder) {
            $escaped = match ($this->getMessageType()) {
                'Markdown' => addcslashes($placeholder->getEvaluatedValue(), '_*`['),
                'MarkdownV2' => addcslashes($placeholder->getEvaluatedValue(), '_*[]()~`>#+-=|{}.!'),
                'HTML' => htmlentities($placeholder->getEvaluatedValue()),
                default => (string) $placeholder->getEvaluatedValue(),
            };
            $message = Str::replace($placeholder->getOriginalMask(), $escaped, $message);
        }
        return $message;
    }

    protected function getMessageType(): ?string
    {
        $type = Arr::get($this->settingBag, 'format_message');
        if ($type === 'Html') return 'HTML';
        return in_array($type, ['MarkdownV2', 'HTML', 'Markdown'], true) ? $type : null;
    }

    public function getBotId(): string
    {
        return Arr::get($this->settingBag, 'bot_id');
    }

    public function getChatId(LeadModel $lead): ?int
    {
        if (!$this->isChatFromField()) {
            return Arr::get($this->settingBag, 'chat_id');
        }

        $fieldId = Arr::get($this->settingBag, 'chat_field_id');
        if (!$fieldId) {
            return null;
        }

        $field = $lead->getCustomFieldsValues()?->getBy('fieldId', (int) $fieldId);
        $rawValue = $field?->getValues()?->first()?->getValue();

        return $rawValue !== null && $rawValue !== '' ? (int) $rawValue : null;
    }

    public function isChatFromField(): bool
    {
        return Arr::get($this->settingBag, 'source_type') === 'lead_fields';
    }
}
