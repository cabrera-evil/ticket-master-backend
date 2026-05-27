<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'ADMIN';
    case Company = 'COMPANY';
    case User = 'USER';
}
