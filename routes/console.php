<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('bots:check-webhook')->everySixHours();
Schedule::command('notifications:clear')->daily();
