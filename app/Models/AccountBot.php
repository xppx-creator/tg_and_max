<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBot extends Model
{
    protected $fillable = [
        'account_id',
        'bot_id',
    ];

    public function accounts(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    public function bots(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}

