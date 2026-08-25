<?php

namespace Makeroi\Analitics\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class AnalyticsLogEntry extends Model
{
    protected $table = 'analytics_log_entries';

    protected $fillable = [
        'logged_at',
        'title',
        'channel',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];
}
