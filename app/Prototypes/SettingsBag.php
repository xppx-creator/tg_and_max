<?php

namespace App\Prototypes;

// Тут должна быть зависимость из библиотеки доступа к либе уже нет
class SettingsBag extends BaseSettingsBag
{
    public function __construct(array $items)
    {
        parent::__construct($items);
    }
}
