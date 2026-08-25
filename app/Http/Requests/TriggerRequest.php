<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TriggerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bot_id' => 'nullable|integer|exists:bots,id',
            'label' => 'required|string',

            'source_type' => 'required|string|in:chat_list,lead_fields',
            'chat_id' => 'required_if:source_type,chat_list|nullable|integer|exists:chats,id',
            'chat_field_id' => 'required_if:source_type,lead_fields|nullable|integer',

            'field_id' => 'nullable|integer',
            'message' => 'required|string',

            'buttons' => 'present|array|max:20',
            'buttons.*.id' => 'required|string',
            'buttons.*.label' => 'required|string',
            'buttons.*.button_type' => 'required|string|in:url,salesbot',

            'buttons.*.url_button' => 'required_if:buttons.*.button_type,url|nullable|string|max:2048',
            'buttons.*.salesbot_id' => 'required_if:buttons.*.button_type,salesbot|nullable|integer',
            'buttons.*.action_after' => 'nullable|string|in:delete_one_button,delete_all_button',
            'buttons.*.sort' => 'required|integer',

            'format_message' => 'nullable|string|in:MarkdownV2,Markdown,Html,HTML',
        ];
    }
}
