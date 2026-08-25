<?php

namespace Makeroi\Analitics\Services;

use Illuminate\Http\Request;

class AnalyticsBootstrap
{
    public function __construct(
        private readonly PanelRegistry $panels,
    ) {}

    /**
     * Payload для window.{spa.window_key} при отдаче SPA index.html.
     *
     * @return array<string, mixed>
     */
    public function payload(Request $request): array
    {
        $apiBase = '/'.trim((string) config('makeroi.analitycs.route.prefix', 'api/v1'), '/');
        $spaBase = '/'.trim((string) config('makeroi.analitycs.spa.route_prefix', 'analytics'), '/');

        $panelEntries = [];

        foreach ($this->panels->all() as $panel) {
            $panelEntries[] = $panel->toBootstrap();
        }

        $default = $this->panels->defaultPanel();
        $defaultSlug = $default !== null ? $default::slug() : null;
        $defaultEndpoint = $default?->endpointPath();

        $payload = [
            'apiBase' => $apiBase,
            'spaBase' => $spaBase,
            'defaultPanel' => $defaultSlug,
            'panels' => $panelEntries,
            'tableEndpoint' => $defaultEndpoint,
            'token' => $this->resolveToken($request),
            'locale' => $request->query(
                'lang',
                $request->query('locale', (string) config('makeroi.analitycs.spa.locale', 'ru')),
            ),
            'theme' => $request->query('theme', config('makeroi.analitycs.spa.theme', 'light')),
            'query' => $request->query(),
        ];

        return array_merge($payload, $this->extra($request));
    }

    public function windowKey(): string
    {
        return (string) config('makeroi.analitycs.spa.window_key', '__MAKEROI_ANALITYCS__');
    }

    /**
     * @return array<string, mixed>
     */
    private function extra(Request $request): array
    {
        $extra = config('makeroi.analitycs.spa.bootstrap_extra');

        if (is_callable($extra)) {
            $result = $extra($request);

            return is_array($result) ? $result : [];
        }

        return is_array($extra) ? $extra : [];
    }

    private function resolveToken(Request $request): ?string
    {
        $token = $request->query('token');

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $header = $request->bearerToken();

        return $header !== null && $header !== '' ? $header : null;
    }
}
