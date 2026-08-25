<?php

namespace App\Http\Controllers\V0;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountChat;
use App\Models\Bot;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function listChat(Request $request, Bot $bot)
    {
        return $this->chatsQuery($request, $bot)->paginate(
            $request->integer('per_page', 20)
        );
    }

    public function refresh(Request $request, Bot $bot)
    {
        return $this->chatsQuery($request, $bot)->paginate(
            $request->integer('per_page', 20)
        );
    }

    public function delete(Bot $bot, Chat $chat)
    {
        $account = $this->resolveAccount();

        AccountChat::where('account_id', $account->id)
            ->where('chat_id', $chat->id)
            ->delete();

        return response()->json(['message' => 'ok']);
    }

    private function chatsQuery(Request $request, Bot $bot)
    {
        $query = Chat::where('bot_id', $bot->id);

        if ($bot->type === 'common') {
            $account = $this->resolveAccount();
            $query->whereHas('accountChats', fn ($q) => $q->where('account_id', $account->id));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function resolveAccount(): Account
    {
        $amocrmId = KAuth::getAccount()->getAmocrmId();

        return Account::where('amocrm_id', $amocrmId)->firstOrFail();
    }
}
