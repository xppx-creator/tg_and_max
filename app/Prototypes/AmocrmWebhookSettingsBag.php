<?php

namespace App\Prototypes;
class AmocrmWebhookSettingsBag extends  BaseWebhookSettingBag
{
    function rules(): array
    {
        return [
            'trigger_uuid' => 'required|string',
            'bot_id' => 'required|string',
            'source_type' => 'required|string',
            'chat_id' => 'required|int',
            'chat_field_id' => 'nullable|int',
            'field_id' => 'required|int',
            'message' => 'required|string',
            'buttons' => 'required|array',
            'format_message' => 'string|in:MarkdownV2,Markdown,Html,HTML',
        ];
    }
}
