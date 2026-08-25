<?php

namespace App\Http\Controllers\V0;

use App\Http\Controllers\Controller;
use App\Http\Requests\TriggerRequest;
use App\Models\Account;
use App\Models\Trigger;
use Illuminate\Http\JsonResponse;
use Makeroi\Amocrm\Kernel\Auth\KAuth;

class TriggerController extends Controller
{
    public function save(TriggerRequest $request, ?Trigger $trigger = null): JsonResponse
    {
        $account = $this->resolveAccount();

        if ($trigger !== null) {
            $this->authorizeTrigger($trigger, $account);
        }

        $data = $request->validated();

        $trigger = Trigger::updateOrCreate(
            ['id' => $trigger?->id],
            [
                'account_id' => $account->id,
                'bot_id' => data_get($data, 'bot_id'),
                'label' => data_get($data, 'label'),
                'source_chat' => data_get($data, 'source_type'),
                'chat_id' => data_get($data, 'chat_id'),
                'chat_field_id' => data_get($data, 'chat_field_id'),
                'field_id' => data_get($data, 'field_id'),
                'message' => data_get($data, 'message'),
                'buttons' => data_get($data, 'buttons', []),
                'format_message' => data_get($data, 'format_message'),
            ]
        );

        return response()->json(['uuid' => $trigger->id]);
    }

    public function delete(Trigger $trigger): JsonResponse
    {
        $this->authorizeTrigger($trigger);

        $trigger->delete();

        return response()->json(['message' => 'ok']);
    }

    private function resolveAccount(): Account
    {
        $amocrmId = KAuth::getAccount()->getAmocrmId();

        return Account::where('amocrm_id', $amocrmId)->firstOrFail();
    }

    private function authorizeTrigger(Trigger $trigger, ?Account $account = null): void
    {
        $account ??= $this->resolveAccount();

        abort_if($trigger->account_id !== $account->id, 403, 'Триггер принадлежит другому аккаунту');
    }
}
