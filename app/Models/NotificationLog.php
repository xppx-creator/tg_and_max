<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationLog extends Model
{
    protected $fillable = [
        'account_id',
        'lead_id',
        'platform',
        'bot_id',
        'bot_label',
        'chat_id',
        'chat_label',
        'trigger_id',
        'trigger_type',
        'trigger_name',
        'message',
        'format_message',
        'error_message',
        'message_ids',
        'status',
        'field_id',
        'source_type',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'message_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function triggerButtonLogs(): HasMany
    {
        return $this->hasMany(TriggerButtonLog::class);
    }

    public function attemptLogs(): HasMany
    {
        return $this->hasMany(AttemptLog::class, 'notification_id');
    }
}
