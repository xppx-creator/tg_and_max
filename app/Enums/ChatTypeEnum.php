<?php

namespace App\Enums;

enum ChatTypeEnum: string
{
    case GROUP = 'group';
    case PRIVATE_MESSAGE = 'private_message';
}
