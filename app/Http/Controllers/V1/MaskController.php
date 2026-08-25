<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

class MaskController extends Controller
{
    public function list()
    {
        return response()->json([
            'masks' => Masks::list(),
            'regex' => '^{{[a-z]+\.[a-z]+((:[a-z]+)|(:[a-z]+\([a-zA-Z ]+\)))?}}$'
        ]);
    }
}
