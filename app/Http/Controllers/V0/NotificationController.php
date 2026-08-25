<?php

namespace App\Http\Controllers\V0;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsAppJwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Makeroi\Analitics\Panels\AnalyticsPanel;
use Makeroi\Analitics\Services\PanelRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class NotificationController extends Controller
{
    /**
     * Список дашбордов для submenu / iframe-host (без выпуска JWT).
     *
     * @response 200 {"data":[{"id":"table","name":"Оповещение","url":"https://notification-plus.makeroi.tech/analytics?panel=table"}]}
     */
    public function notifications(PanelRegistry $panels): JsonResponse
    {
        return response()->json(['data' => $this->notificationItems($panels)]);
    }

    /**
     * Стартовый запрос раздела аналитики: amo-токен (KAuth) + опциональный type
     * → app JWT и параметры дашборда(ов) для iframe.
     *
     * @queryParam type string optional slug панели (например table). Без type — все панели.
     *
     * @response 200 {"data":[{"id":"table","name":"Оповещение","url":"https://notification-plus.makeroi.tech/analytics?panel=table"}]}
     */
    public function start(Request $request, PanelRegistry $panels, AnalyticsAppJwt $jwt): JsonResponse
    {
        $type = $request->input('type', $request->query('type'));
        $type = is_string($type) && $type !== '' ? $type : null;

        $items = $this->notificationItems($panels, $type);

        if ($type !== null && $items === []) {
            throw new NotFoundHttpException("Analytics dashboard [{$type}] not found.");
        }

        return response()->json([
            'data' => array_merge($jwt->issueFromCurrentKAuth(), [
                'dashboards' => $items,
            ]),
        ]);
    }

    /**
     * @return list<array{id: string, name: string, url: string}>
     */
    private function notificationItems(PanelRegistry $panels, ?string $type = null): array
    {
        $spaUrl = $this->spaBaseUrl();
        $items = [];

        foreach ($panels->all() as $slug => $panel) {
            logger()->debug('Debug', [
                'type' => $type,
                'slug' => $slug
            ]);
            if ($type !== null && $slug !== $type) {

                continue;
            }

            $items[] = $this->toNotificationItem($slug, $panel, $spaUrl);
        }

        return $items;
    }

    /**
     * @return array{id: string, name: string, url: string}
     */
    private function toNotificationItem(string $slug, AnalyticsPanel $panel, string $spaUrl): array
    {
        return [
            'id' => $slug,
            'name' => $panel->title(),
            'url' => $spaUrl.'?panel='.rawurlencode($slug),
        ];
    }

    private function spaBaseUrl(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $spaPrefix = trim((string) config('makeroi.analitycs.spa.route_prefix', 'analytics'), '/');

        return $base.'/'.$spaPrefix;
    }
}
