<?php

namespace App\Http\Controllers\V0\Webhook;

use App\Console\Commands\AccountCommand;
use App\Console\Commands\CommandReplySender;
use App\Console\Commands\ConnectCommand;
use App\Console\Commands\DisconnectCommand;
use App\Console\Commands\StartCommand;
use App\DTO\UpdateDTO;
use App\Enums\UpdateTypeEnum;
use App\Http\Controllers\Controller;
use App\Max\MaxParser;
use App\Models\Bot;
use App\Services\ChatSyncService;
use App\Services\GroupAdminCheck;
use App\Services\SalesbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaxWebhookController extends Controller
{
    private const GUARDED_COMMANDS = ['connect', 'disconnect', 'accounts'];

    public function __construct(
        private  MaxParser $parser,
        private  GroupAdminCheck $adminGuard,
        private  SalesbotService $salesbotService,
        private  ChatSyncService $chatSyncService,
        private  CommandReplySender $replySender
    ) {
    }

    public function handle(Request $request, Bot $bot): JsonResponse
    {
        $rawUpdate = $request->all();
        logger()->debug('MAX: вебхук', ['payload' => $rawUpdate]);

        $update = $this->parser->parse($rawUpdate);

        if ($update === null) {
            return response()->json(['message' => 'Не поддерживаемый формат'], 200);
        }

        match ($update->type) {
            UpdateTypeEnum::CALLBACK_QUERY => $this->salesbotService->handle($update, $bot),
            UpdateTypeEnum::MESSAGE => $this->dispatchMessage($update, $bot),
        };

        return response()->json(['message' => 'ok'], 200);
    }

    private function dispatchMessage(UpdateDTO $update, Bot $bot): void
    {
        if ($update->command === null) {
            $this->chatSyncService->handle($update, $bot);
            return;
        }

        if (in_array($update->command, self::GUARDED_COMMANDS, true) && $update->isGroup) {
            if (!$this->adminGuard->isAdminMax($bot, $update->chatId, $update->senderId)) {
                $this->replySender->reply($bot, $update->chatId, 'Эта команда доступна только администратору группы.');
                logger()->debug('MAX: команда только для админов', ['command' => $update->command]);
                return;
            }
        }

        match ($update->command) {
            'start' => (new StartCommand())->handle($update, $bot),
            'connect' => (new ConnectCommand())->handle($update, $bot),
            'disconnect' => (new DisconnectCommand())->handle($update, $bot),
            'accounts' => (new AccountCommand())->handle($update, $bot),
            default => $this->replySender->reply(
                $bot,
                $update->chatId,
                "Доступные команды:\n/connect {id} — подключить аккаунт\n/disconnect {id} — отключить аккаунт\n/accounts — список подключённых аккаунтов")
        };
    }
}
