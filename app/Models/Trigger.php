<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Symfony\Component\Uid\Uuid;
class Trigger extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'account_id',
        'bot_id',
        'label',
        'source_chat',
        'chat_id',
        'chat_field_id',
        'field_id',
        'message',
        'buttons',
        'format_message',
    ];
    protected $casts = [
        'buttons' => 'array',
    ];
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function newUniqueId(): string
    {
        return (string) Uuid::v7();
    }
}
