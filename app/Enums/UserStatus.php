<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/** users.status — ENUM('active','locked'). */
enum UserStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Hoạt động',
            self::Locked => 'Đã khóa',
        };
    }
}
