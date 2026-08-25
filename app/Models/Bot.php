<?php

namespace App\Models;

use App\Enums\PlatformEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bot extends Model
{
    public $fillable = [
        'account_id',
        'bot_id',
        'name',
        'username',
        'type',
        'platform',
        'avatar_url',
        'welcome_message',
        'token',
        'secret_token',
        'is_active',
        'last_ping_at'
    ];

    protected $casts = [
        'platform' => PlatformEnum::class,
        'is_active' => 'boolean',
        'last_ping_at' => 'datetime'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
    public function triggers(): HasMany
    {
        return $this->HasMany(Trigger::class);
    }

}
