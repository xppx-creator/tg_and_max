<?php

namespace App\Prototypes;

use Makeroi\Amocrm\Kernel\Settings\SettingsBag as BaseSettingsBag;

class SettingsBag extends BaseSettingsBag
{
    public function __construct(array $items)
    {
        parent::__construct($items);
    }
}
