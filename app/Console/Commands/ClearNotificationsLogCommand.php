<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use Illuminate\Console\Command;


class ClearNotificationsLogCommand extends Command
{
    protected $signature = 'notifications:clear';
    protected $description = 'Удаляет уведомления старше 14 дней';

    public function handle(): int
    {
        $deleted = NotificationLog::where('created_at', '<', now()->subDays(14))->delete();

        $this->info("Удалено уведомлений: {$deleted}");

        return self::SUCCESS;
    }
}
