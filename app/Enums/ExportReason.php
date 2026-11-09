<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/** exports.reason — ENUM('sale','transfer'). Chuyển kho (transfer) bắt buộc có kho đích. */
enum ExportReason: string
{
    use HasOptions;

    case Sale = 'sale';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Bán hàng',
            self::Transfer => 'Chuyển kho',
        };
    }
}
