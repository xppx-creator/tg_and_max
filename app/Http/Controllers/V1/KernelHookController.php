<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\AuthBag;
use Illuminate\Http\Request;
use App\Models\Account;

class KernelHookController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->query('token') != '34wACgLC57RRQvAQ2e8bGikn25CWseDY', 403);
        $amocrmId = $request->json('amocrm_id');
        abort_if(is_null($amocrmId), 400, 'Некорректное тело запроса');

        logger()->debug('Получили хук о новой установке', [
            'integration_code' => $request->json('widget_code', ''),
            'account_id' => $amocrmId,
            'hash' => $request->json('hash', ''),
        ]);

        $account = Account::firstOrCreate(
            ['amocrm_id' => $amocrmId],
            ['domain' => $request->json('domain', ''), 'is_active' => true]
        );

        AuthBag::updateOrCreate(
            ['account_id' => $account->id, 'integration_code' => $request->json('widget_code', '')],
            ['hash' => $request->json('hash', '')]
        );
        return response()->json([]);
    }
}
