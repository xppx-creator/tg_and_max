<?php

namespace App\Analytics\Panels;

use App\Models\Account;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Middleware\AnalyticsAppJwtMiddleware;
class NotificationsAnalyticsPanel extends ModelAnalyticsPanel
{
    public static function slug(): string
    {
        return 'notifications';
    }

    public function title(): string
    {
        return 'Оповещения Плюс';
    }

    public function model(): string
    {
        return NotificationLog::class;
    }

    public function middleware(): array
    {
        return [AnalyticsAppJwtMiddleware::class];
    }

    public function query(): Builder
    {
        $amocrmId = KAuth::getAccount()->getAmocrmId();
        $account = Account::where('amocrm_id', $amocrmId)->firstOrFail();

        return $this->newQuery()->where('account_id', $account->id);
    }

    public function columns(): array
    {
        return [
            Column::make('started_at', 'Время')
                ->sortable()
                ->filterDateRange('period', 'Период')
                ->asTimestamp()
                ->viewType('date', ['format' => 'DD.MM.YYYY HH:mm']),

            Column::make('platform', 'Платформа')
                ->attribute('platform')
                ->filterSelect('platform', 'Платформа', [
                    ['value' => 'telegram', 'title' => 'Telegram'],
                    ['value' => 'max', 'title' => 'MAX'],
                ]),

            Column::make('bot_label', 'Бот')
                ->attribute('bot_label')
                ->filterStringContains('bot_label', 'Бот'),

            Column::make('chat_label', 'Чат')
                ->attribute('chat_label')
                ->filterStringContains('chat_label', 'Чат'),

            Column::make('lead_id', 'Сделка')
                ->attribute('lead_id')
                ->sortable(),

            Column::make('status', 'Статус')
                ->attribute('status')
                ->filterSelect('status', 'Статус', [
                    ['value' => 'pending', 'title' => 'В процессе'],
                    ['value' => 'sent', 'title' => 'Отправлено'],
                    ['value' => 'failed', 'title' => 'Ошибка'],
                ]),

            Column::make('detail', '')
                ->asDetailAction('Подробнее'),
        ];
    }

    public function metrics(Request $request): array
    {
        $query = $this->query();

        return [
            ['label' => 'Всего', 'value' => (clone $query)->count()],
            ['label' => 'Отправлено', 'value' => (clone $query)->where('status', 'sent')->count()],
            ['label' => 'Ошибок', 'value' => (clone $query)->where('status', 'failed')->count()],
        ];
    }

    protected function detail(): DetailModal
    {
        return DetailModal::make()
            ->titleBind('row.chat_label', 'Уведомление')
            ->stack('16px', [
                DetailNode::keyValueList('Данные', [
                    DetailNode::kv('Статус', DetailNode::bind('detail.status')),
                    DetailNode::kv('Сообщение', DetailNode::bind('detail.message')),
                    DetailNode::kv('Ошибка', DetailNode::bind('detail.error_message')),
                ]),
                DetailNode::actionLog('История попыток', DetailNode::bind('detail.attempts')),
            ]);
    }

    protected function detailPayload($model): array
    {
        return [
            'status' => $model->status,
            'message' => $model->message,
            'error_message' => $model->error_message,
            'attempts' => $model->attemptLogs()
                ->orderBy('attempts_number')
                ->get()
                ->map(fn ($attempt) => [
                    'title' => "Попытка {$attempt->attempts_number}: {$attempt->status}",
                    'description' => $attempt->details_attempts,
                ])
                ->all(),
        ];
    }
}
