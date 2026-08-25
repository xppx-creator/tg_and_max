<?php

return [
    /* Для использования в примечаниях */
    'service_name' => 'makeROI | Оповещения в Telegram',

    /* Для всяких хэшей */
    'salt' => 'PfQ8CWg83eU6cUXE8d3vSjgS9E7bcePCqWj8LJJkXk9TEHoDvTZJ9CKCRn6d',

    'should_report_sentry' => env('SHOULD_REPORT_SENTRY', true),

    /* Для авторизации в Kernel */
    'project' => [
        'code' => 'makeroi_tg_notifies',
        'always_use_production' => true,
    ],

    'kernel' => [
        'settings_bag' => App\Prototypes\SettingsBag::class,
        'secret' => 'q3bFT8rQ8T48kM5qHj7KLHVCWNSvu8Q3yvyqPwomcfqjjPCPfQ8CWg83eU6cUXE8d3vSjgS9E7bcePCqWj8LJJkXk9TEHoDvTZJ9CKCRn6dzV3YTNUQWYiE7T9sfDGjw',
    ],
];
