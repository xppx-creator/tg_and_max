<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsAppJwt;
use App\Services\Analytics\AnalyticsJwtAuthContext;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsAppJwtMiddleware
{
    public function __construct(
        private readonly AnalyticsAppJwt $jwt,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            throw new AuthenticationException('Не предоставлен Bearer-токен аналитики');
        }

        $claims = $this->jwt->decode($token);

        app()->instance('makeroi_auth', new AnalyticsJwtAuthContext(
            AccountPrototype::make([
                'amocrm_id' => $claims['amocrm_id'],
                'domain' => $claims['domain'],
                'name' => '',
            ]),
            WidgetPrototype::make([
                'code' => $claims['widget_code'],
                'name' => '',
            ]),
        ));

        $request->attributes->set('analytics_account_id', $claims['account_id']);

        return $next($request);
    }
}
