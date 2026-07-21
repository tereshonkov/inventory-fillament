<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case VIEWER = 'viewer';
    case EDITOR = 'editor';
}
