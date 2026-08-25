<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class AuthBag extends Model
{
    public $incrementing = false;
    protected $guarded = null;
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * @throws KernelApiException
     */
    public function auth(): void
    {

        KAuth::auth((HashCredentialsBag::make(
            $this->hash,
            $this->account->amocrm_id,
            $this->integration_code
        )));
    }
}
