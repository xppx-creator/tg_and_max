<?php

namespace Makeroi\Analitics\Contracts;

/**
 * Собирает view-конфиг панели (контракт SPA, как table.conf.json)
 * из входных данных конфигуратора (panel source).
 */
interface TablePanelConfigBuilder
{
    /**
     * @param  array<string, mixed>  $source  декодированный panel source (см. panel.source.example.json)
     * @return array<string, mixed> view config: bulkActions, columns, detail, …
     */
    public function build(array $source): array;
}
