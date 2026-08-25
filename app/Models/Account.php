<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    protected $fillable = [
        'amocrm_id',
        'domain',
        'is_active',
    ];
    protected $casts =[
        'is_active' => 'boolean'
    ];

    public function ownBots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function authBag(): HasOne
    {
        return $this->hasOne(AuthBag::class);
    }


    public function accountChats(): HasMany
    {
        return $this->hasMany(AccountChat::class);
    }

    public function accountBots(): HasMany
    {
        return $this->hasMany(AccountBot::class);
    }
    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
    public function triggers(): HasMany
    {
        return $this->hasMany(Trigger::class);
    }
    public function chats(): BelongsToMany
    {
        return $this->BelongsToMany(Chat::class, 'account_chats')->withTimestamps();
    }
    public function commonBots(): BelongsToMany
    {
        return $this->BelongsToMany(Bot::class, 'account_bots')
            ->where('bots.type', BotTypeEnum::COMMON)
            ->withTimestamps();
    }
}
