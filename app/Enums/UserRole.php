<?php

namespace App\Enums;

enum UserRole: string
{
    case CREATOR = 'creator';
    case BRAND   = 'brand';
    case ADMIN   = 'admin';
}