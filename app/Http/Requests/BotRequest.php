<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'platform' => 'nullable|string|in:max,telegram',
            'bot_type' => 'required|string|in:own,common',
            'token' => 'required|string',
            'welcome_message' => 'nullable|string',
        ];
    }
}
