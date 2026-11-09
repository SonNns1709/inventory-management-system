<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Trạng thái chứng từ — dùng chung cho imports, exports, stock_adjustments.
 *
 * draft     : phiếu nháp, được sửa/hủy, CHƯA làm thay đổi tồn kho.
 * confirmed : đã xác nhận (ghi sổ) — bất biến.
 * cancelled : phiếu nháp đã bị hủy.
 */
enum DocumentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Confirmed => 'Đã xác nhận',
            self::Cancelled => 'Đã hủy',
        };
    }
}
