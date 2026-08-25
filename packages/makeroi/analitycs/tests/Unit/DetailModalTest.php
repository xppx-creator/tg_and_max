<?php

use Makeroi\Analitics\Detail\DetailModal;
use Makeroi\Analitics\Detail\DetailNode;
use packages\makeroi\analitycs\src\Services\PanelViewConfigAssembler;
use Makeroi\Analitics\Columns\Column;

it('builds detail modal config via fluent API', function () {
    $detail = DetailModal::make()
        ->titleBind('detail.scenario_title', 'Копирование')
        ->width('680px')
        ->stack('18px', [
            DetailNode::outcomeBanner(
                DetailNode::bind('detail.outcome_title'),
                DetailNode::bind('detail.outcome_description'),
                DetailNode::bind('detail.outcome_color'),
            ),
            DetailNode::keyValueList('Детали', [
                DetailNode::kv('Статус', DetailNode::badge(
                    DetailNode::bind('detail.status_label'),
                    DetailNode::bind('detail.status_color'),
                )),
                DetailNode::kv('Инициатор', DetailNode::bind('detail.initiator', '—')),
            ]),
            DetailNode::actionLog('Лог', DetailNode::bind('detail.history')),
        ])
        ->toArray();

    expect(data_get($detail, 'title.$bind'))->toBe('detail.scenario_title')
        ->and(data_get($detail, 'title.$default'))->toBe('Копирование')
        ->and(data_get($detail, 'width'))->toBe('680px')
        ->and(data_get($detail, 'template.type'))->toBe('v-stack')
        ->and(data_get($detail, 'template.children.0.type'))->toBe('outcome-banner')
        ->and(data_get($detail, 'template.children.1.type'))->toBe('key-value-list')
        ->and(data_get($detail, 'template.children.2.type'))->toBe('action-log');
});

it('wraps detail template with modal-skeleton in assembler', function () {
    $assembled = app(PanelViewConfigAssembler::class)->assemble(
        columns: [Column::make('when', 'Запуск')],
        detail: DetailModal::make()
            ->title('Test')
            ->stack('12px', [
                DetailNode::keyValueList('Детали', [
                    DetailNode::kv('A', DetailNode::bind('detail.a')),
                ]),
            ])
            ->toArray(),
    );

    expect(data_get($assembled, 'detail.template.type'))->toBe('modal-skeleton')
        ->and(data_get($assembled, 'detail.template.props.loading.$bind'))->toBe('loading')
        ->and(data_get($assembled, 'detail.template.children.0.type'))->toBe('v-stack');
});
