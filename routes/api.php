<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\V0\BotController;
use App\Http\Controllers\V0\ChatController;
use App\Http\Controllers\V0\NotificationController;
use App\Http\Controllers\V0\TriggerController;
use App\Http\Controllers\V0\Webhook\MaxWebhookController;
use App\Http\Controllers\V0\Webhook\TelegramWebhookController;
use App\Http\Controllers\V1\KernelHookController;
use App\Http\Controllers\V1\MaskController;
use App\Http\Middleware\VerifyMaxWebhookMiddleware;
use App\Jobs\AmocrmWebhookDpProcessJob;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyTelegramWebhookMiddleware;

Route::prefix('/v0')->group(function () {

    MakeroiRoute::webhookDp(AmocrmWebhookDpProcessJob::class, 'notification');

    Route::prefix('webhooks')->group(function () {
        Route::post('tg/{bot}', [TelegramWebhookController::class, 'handle']);
//            ->middleware(VerifyTelegramWebhookMiddleware::class);
        Route::post('max/{bot}', [MaxWebhookController::class, 'handle'])
            ->middleware(VerifyMaxWebhookMiddleware::class);
    });

    Route::middleware(KAuthWidgetMiddleware::class)->group(function () {

        Route::prefix('triggers')->group(function () {
            Route::post('/', [TriggerController::class, 'save']);
            Route::patch('/{trigger}', [TriggerController::class, 'save']);
            Route::delete('/{trigger}', [TriggerController::class, 'delete']);
        });

        Route::prefix('analytics')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'notifications']);
            Route::post('/start', [NotificationController::class, 'start']);
        });

        Route::prefix('bots')->group(function () {
            Route::get('/', [BotController::class, 'showList']);
            Route::post('/', [BotController::class, 'create']);
            Route::get('/{bot}', [BotController::class, 'showBot']);
            Route::patch('/{bot}', [BotController::class, 'edit']);
            Route::delete('/{bot}', [BotController::class, 'delete']);
            Route::prefix('{bot}/chats')->group(function () {
                Route::get('/', [ChatController::class, 'listChat']);
                Route::post('/refresh', [ChatController::class, 'refresh']);
                Route::delete('/{chat}', [ChatController::class, 'delete']);
            });
        });
    });
});

Route::group(['prefix' => 'v1'], function () {
    Route::post('/kernel.activate', [KernelHookController::class, 'index'])->name('kernel.activate');
    Route::get('masks', [MaskController::class, 'list']);
});


