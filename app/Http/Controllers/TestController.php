<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    private const AMO_DOMAIN = 'cdscdscsdc.amocrm.ru';
    private const AMO_ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2Nkc2Nkc2NzZGMuYW1vY3JtLnJ1IiwiYXVkIjoiaHR0cHM6Ly9rZXJuZWwubWFrZXJvaS50ZWNoIiwianRpIjoiZGMwMWZjM2YtMDBlZi00NGQ2LTlkZDUtZGQzYzgyNTJmNWI2IiwiaWF0IjoxNzg2NjI4MzkzLCJuYmYiOjE3ODY2MjgzOTMsImV4cCI6MTc4NjYzMDE5MywiYWNjb3VudF9pZCI6MzMyMTA0ODYsInN1YmRvbWFpbiI6ImNkc2Nkc2NzZGMiLCJjbGllbnRfdXVpZCI6ImI3MjRjZTc5LTg3OTEtNDVhYi1hYjZmLTY3OTE4MTU2NTQ0MCIsInVzZXJfaWQiOjE0MTMxMTQyLCJpc19hZG1pbiI6dHJ1ZX0._zrbfHudi2e5riLD-N78Nx4abhWDQVXFuXSncFU_U3U';
    public function handle(Request $request)
    {
        $response = Http::withToken(self::AMO_ACCESS_TOKEN)
            ->get('https://' . self::AMO_DOMAIN . '/api/v4/leads', [
                'page'  => $request->get('page', 1),
                'limit' => $request->get('limit', 250),
            ]);

        return response()->json([
            'status'  => $response->status(),
            'headers' => $response->headers(),
            'body'    => $response->json(),
        ], $response->status());
    }
}
