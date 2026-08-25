<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriggerButtonLog extends Model
{
    protected $fillable = [
        'notification_log_id',
        'label',
        'button_type',
        'url_button',
        'salesbot_id',
        'action_after',
        'callback_data',
        'sort',
    ];

    public function notificationLog(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class);
    }
}
