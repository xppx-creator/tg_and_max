<?php

namespace App\Http\Middleware;

use App\Models\Bot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMaxWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $bot = $request->route('bot');

        if (! $bot instanceof Bot) {
            abort(404);
        }
        $secret = $request->header('X-Max-Bot-Api-Secret');

        if (!$secret || ! $bot->secret_token || ! hash_equals($bot->secret_token, $secret)) {
            abort(401);
        }

        return $next($request);
    }
}
