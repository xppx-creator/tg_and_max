<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountChat extends Model
{
    protected $fillable = [
        'account_id',
        'chat_id',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }



}
