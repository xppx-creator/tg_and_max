<?php

namespace App\Services\Analytics;

use Makeroi\Amocrm\Exceptions\Kernel\KernelNoCredentialsException;
use Makeroi\Amocrm\Kernel\Auth\Prototypes\AccountPrototype;
use Makeroi\Amocrm\Kernel\Auth\Prototypes\WidgetPrototype;
use Makeroi\Amocrm\Kernel\Settings\SettingsBag;

/**
 * Минимальный контекст makeroi_auth для panel API после проверки app JWT.
 * Достаточно для Account::forCurrentAccount() / KAuth::check() в панелях.
 */
final class AnalyticsJwtAuthContext
{
    private readonly SettingsBag $settingsBag;

    public function __construct(
        private readonly AccountPrototype $account,
        private readonly WidgetPrototype $widget,
        ?SettingsBag $settingsBag = null,
    ) {
        $this->settingsBag = $settingsBag ?? new SettingsBag([]);
    }

    public function check(): bool
    {
        return true;
    }

    public function getAccount(): AccountPrototype
    {
        return $this->account;
    }

    public function getWidget(): WidgetPrototype
    {
        return $this->widget;
    }

    public function getSettingsBag(): SettingsBag
    {
        return $this->settingsBag;
    }

    public function getManager(): never
    {
        throw new KernelNoCredentialsException('Manager is not available in analytics JWT context');
    }

    public function getApiClient(): never
    {
        throw new KernelNoCredentialsException('API client is not available in analytics JWT context');
    }

    public function getInstall(): never
    {
        throw new KernelNoCredentialsException('Install is not available in analytics JWT context');
    }

    public function getKernelCredentialsBag(): never
    {
        throw new KernelNoCredentialsException('Kernel credentials are not available in analytics JWT context');
    }
}
