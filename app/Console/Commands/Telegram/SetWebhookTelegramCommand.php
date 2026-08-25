<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetWebhookTelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-webhook-telegram-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://mamie-geothermal-tamia.ngrok-free.dev/api/v0/webhooks/tg/1';
        $response = Telegram::setWebhook([
            'url' => $url,
            'secret' => '123abc',
            ]);
        logger()->info('Вебхук telegram установлен', [
            'response' => $response,
        ]);

    }
}
