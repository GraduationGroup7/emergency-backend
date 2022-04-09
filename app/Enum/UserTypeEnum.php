<?php

namespace App\Enum;

enum UserTypeEnum:string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case AUTHORITY = 'authority';
    case AGENT = 'agent';
}
