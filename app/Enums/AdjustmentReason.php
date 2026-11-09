<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/** stock_adjustment_items.reason — lý do chênh lệch (bắt buộc khi diff_qty <> 0, BR04.03-5). */
enum AdjustmentReason: string
{
    use HasOptions;

    case Loss = 'loss';
    case Damaged = 'damaged';
    case Shrinkage = 'shrinkage';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Loss => 'Thất lạc',
            self::Damaged => 'Hư hỏng',
            self::Shrinkage => 'Hao hụt',
            self::Other => 'Khác',
        };
    }
}
