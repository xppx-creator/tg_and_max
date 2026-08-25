<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptLog extends Model
{
    protected $fillable = [
        'text_attempts',
        'details_attempts',
        'attempts_number',
        'status',
        'event_type',
        'notification_id'
    ];
    public function notification(): BelongsTo
    {
        return $this->BelongsTo(NotificationLog::class);
    }
}
