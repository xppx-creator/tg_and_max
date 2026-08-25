<?php

namespace Makeroi\Analitics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Makeroi\Analitics\Services\AnalyticsBootstrap;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SpaController
{
    public function __construct(
        private readonly AnalyticsBootstrap $bootstrap,
    ) {}

    public function __invoke(Request $request, ?string $path = null): BinaryFileResponse|IlluminateResponse|Response
    {
        $dist = $this->distPath();
        $path = ltrim($path ?? '', '/');

        if ($path === '' || ! str_contains($path, '.')) {
            return $this->indexResponse($request, $dist.'/index.html');
        }

        $realDist = realpath($dist);

        if ($realDist === false) {
            abort(Response::HTTP_NOT_FOUND, 'Analytics SPA is not built. Run: npm run build in packages/makeroi/analitycs');
        }

        $candidate = realpath($dist.DIRECTORY_SEPARATOR.$path);

        if ($candidate === false || ! str_starts_with($candidate, $realDist)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($candidate);
    }

    private function indexResponse(Request $request, string $indexPath): IlluminateResponse
    {
        if (! is_file($indexPath)) {
            abort(Response::HTTP_NOT_FOUND, 'Analytics SPA is not built. Run: npm run build in packages/makeroi/analitycs');
        }

        $html = file_get_contents($indexPath);

        if ($html === false) {
            abort(Response::HTTP_NOT_FOUND, 'Analytics SPA index is not readable.');
        }

        // Vite ставит crossorigin на script/link; без CORS-заголовков Chromium
        // не применяет stylesheet (CSSOM пустой → чёрный фон / сломанный layout).
        $html = preg_replace('/\s+crossorigin(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $html) ?? $html;

        $key = $this->bootstrap->windowKey();
        $payload = $this->bootstrap->payload($request);
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );

        $script = '<script>window['.json_encode($key).'] = '.$json.';</script>';

        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', $script."\n</head>", $html);
        } else {
            $html = $script.$html;
        }

        return response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function distPath(): string
    {
        return dirname(__DIR__, 3).'/dist';
    }

    private function fileResponse(string $path): BinaryFileResponse
    {
        if (! is_file($path)) {
            abort(Response::HTTP_NOT_FOUND, 'Analytics SPA is not built. Run: npm run build in packages/makeroi/analitycs');
        }

        $response = response()->file($path, [
            'Content-Type' => $this->mimeFor($path),
            'Access-Control-Allow-Origin' => '*',
        ]);

        return $response;
    }

    private function mimeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'text/javascript; charset=UTF-8',
            'map' => 'application/json; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'html' => 'text/html; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }
}
