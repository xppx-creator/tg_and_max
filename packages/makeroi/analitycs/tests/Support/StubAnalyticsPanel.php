<?php

namespace Makeroi\Analitics\Tests\Support;

use Illuminate\Http\Request;
use Makeroi\Analitics\Panels\AnalyticsPanel;

class StubAnalyticsPanel extends AnalyticsPanel
{
    public static function slug(): string
    {
        return 'stub';
    }

    public function config(): array
    {
        return $this->configFromFile($this->packageConfigStubPath());
    }

    public function data(Request $request): array
    {
        return [
            'total' => 1,
            'rows' => [
                ['id' => 1, 'cells' => ['when' => 1780658520]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Stub panel';
    }

    public function metrics(Request $request): array
    {
        return [
            ['label' => 'Всего', 'value' => 1],
        ];
    }

    public function detailData(Request $request): array
    {
        $id = $request->query('id', $request->input('id'));

        if ($id === null || $id === '') {
            abort(422, 'Query parameter id is required for section=detail.');
        }

        return [
            'id' => (int) $id,
            'message' => 'Stub detail',
        ];
    }
}
