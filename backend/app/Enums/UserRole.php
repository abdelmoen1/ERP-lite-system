<?php

namespace App\Enums;

enum UserRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case EMPLOYEE = 'employee';
}
