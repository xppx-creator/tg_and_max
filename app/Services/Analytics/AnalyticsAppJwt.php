<?php

namespace App\Services\Analytics;

use App\Models\Account;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use InvalidArgumentException;
use UnexpectedValueException;

final class AnalyticsAppJwt
{
    public const TYP = 'analytics';

    /**
     * @return array{token: string, expires_in: int}
     */
    public function issueFromCurrentKAuth(): array
    {
        $account = Account::forCurrentAccount();
        $authAccount = KAuth::getAccount();
        $ttl = $this->ttlSeconds();
        $now = time();

        $payload = [
            'typ' => self::TYP,
            'iat' => $now,
            'exp' => $now + $ttl,
            'account_id' => $account->id,
            'amocrm_id' => $authAccount->getAmocrmId(),
            'domain' => $authAccount->getDomain(),
            'widget_code' => KAuth::getWidget()->getCode(),
        ];

        return [
            'token' => JWT::encode($payload, $this->secret(), 'HS256'),
            'expires_in' => $ttl,
        ];
    }

    /**
     * @return array{account_id: int, amocrm_id: int, domain: string, widget_code: string, exp: int}
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret(), 'HS256'));
        } catch (UnexpectedValueException $e) {
            throw new AuthenticationException('Недействительный токен аналитики: '.$e->getMessage());
        }

        $data = (array) $decoded;

        if (($data['typ'] ?? null) !== self::TYP) {
            throw new AuthenticationException('Недействительный тип токена аналитики');
        }

        foreach (['account_id', 'amocrm_id', 'domain', 'widget_code', 'exp'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new AuthenticationException("В токене аналитики нет поля {$key}");
            }
        }

        return [
            'account_id' => (int) $data['account_id'],
            'amocrm_id' => (int) $data['amocrm_id'],
            'domain' => (string) $data['domain'],
            'widget_code' => (string) $data['widget_code'],
            'exp' => (int) $data['exp'],
        ];
    }

    public function ttlSeconds(): int
    {
        $ttl = (int) config('makeroi.analitycs.app_jwt.ttl', 12 * 3600);

        if ($ttl < 60) {
            throw new InvalidArgumentException('makeroi.analitycs.app_jwt.ttl must be >= 60 seconds');
        }

        return $ttl;
    }

    private function secret(): string
    {
        $secret = config('makeroi.analitycs.app_jwt.secret') ?: config('app.key');

        if (! is_string($secret) || $secret === '') {
            throw new InvalidArgumentException('Analytics JWT secret is not configured');
        }

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded === false) {
                throw new InvalidArgumentException('Invalid base64 APP_KEY for analytics JWT');
            }

            return $decoded;
        }

        return $secret;
    }
}
