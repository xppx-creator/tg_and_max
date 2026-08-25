<?php

namespace Makeroi\Analitics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Makeroi\Analitics\Panels\AnalyticsPanel;
use Makeroi\Analitics\Services\PanelRegistry;
use Symfony\Component\HttpFoundation\Response;

class TableController
{
    public function __construct(
        private readonly PanelRegistry $panels,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $slug = (string) ($request->route()?->defaults['analytics_panel'] ?? '');

        if ($slug === '') {
            abort(Response::HTTP_NOT_FOUND, 'Analytics panel is not specified for this route.');
        }

        $panel = $this->panels->get($slug);
        $section = $request->query('section', 'config');

        return match ($section) {
            'config' => response()->json($panel->config()),
            'data' => response()->json($this->resolveData($request, $panel)),
            'metrics' => response()->json($panel->dashboard($request)),
            'detail' => response()->json([
                'detail' => $panel->detailData($request),
            ]),
            default => response()->json([
                'message' => 'Unknown section. Use section=config, section=data, section=metrics or section=detail.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY),
        };
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private function resolveData(Request $request, AnalyticsPanel $panel): array
    {
        $request->merge(['per_page' => $panel->resolvePerPage($request)]);

        return $panel->data($request);
    }
}
