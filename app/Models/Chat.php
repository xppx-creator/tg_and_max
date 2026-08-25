<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Chat extends Model
{
    protected $fillable = [
        'external_id',
        'title',
        'type',
        'bot_id',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
    public function triggers(): HasMany
    {
        return $this->HasMany(Trigger::class);
    }
    public function accountChats(): HasMany
    {
        return $this->hasMany(AccountChat::class);
    }
}
