<?php

namespace App\Enums;

enum UpdateTypeEnum: string
{
    case CALLBACK_QUERY = 'callback_query';
    case MESSAGE = 'message';
}
