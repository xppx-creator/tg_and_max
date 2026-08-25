<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IdempotencyService
{
    private const TTL_SECONDS = 4;

    public function lock(int|string $key): bool
    {
        return Cache::add($this->cacheKey($key), true, self::TTL_SECONDS);
    }

    public function release(int|string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(int|string $key): string
    {
        return "trigger_button_log:{$key}";
    }
}
