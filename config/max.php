<?php

return [
    'bots' => [
        'mybot' => [
            'token' => env('MAX_BOT_TOKEN', 'f9LHodD0cOJ8lKsbo5Tjk1s9hE7aTa2lS2vwQZBWOmFq_q6W9JqZK3SeJGr6Jos-GjuthufMxKvIcejvbDYX'),
            'certificate_path' => env('MAX_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url' => env('MAX_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            /*
            'allowed_updates' => null,
            'commands' => [
                // Acme\Project\Commands\MyTelegramBot\BotCommand::class
            ],
        ],

        //        'mySecondBot' => [
        //            'token' => '123456:abc',
        //        ],
    ],
    */
        ]
    ]
];
