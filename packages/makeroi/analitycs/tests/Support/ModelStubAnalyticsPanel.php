<?php

namespace Makeroi\Analitics\Tests\Support;

use Makeroi\Analitics\Columns\Column;
use packages\makeroi\analitycs\src\Panels\ModelAnalyticsPanel;

class ModelStubAnalyticsPanel extends ModelAnalyticsPanel
{
    public static function slug(): string
    {
        return 'logs';
    }

    public function model(): string
    {
        return AnalyticsLogEntry::class;
    }

    public function columns(): array
    {
        return [
            Column::make('when', 'Когда')
                ->attribute('logged_at')
                ->sortable()
                ->filterDateRange('when_range', 'Период')
                ->asTimestamp()
                ->viewType('date', ['format' => 'DD.MM.YYYY HH:mm'])
                ->width(200),
            Column::make('title', 'Заголовок')
                ->filterStringContains('title_str', 'Заголовок', 'Поиск')
                ->sortable(),
            Column::make('channel', 'Канал')
                ->filterSelect('channel_sel', 'Канал', [
                    ['value' => 'TG', 'title' => 'Telegram'],
                    ['value' => 'MAX', 'title' => 'MAX'],
                ]),
        ];
    }

    public function title(): string
    {
        return 'Log entries';
    }

    public function metrics(\Illuminate\Http\Request $request): array
    {
        $total = $this->query()->count();

        return [
            ['label' => 'Всего записей', 'value' => $total],
        ];
    }
}
