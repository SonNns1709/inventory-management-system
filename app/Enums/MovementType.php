<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/** stock_movements.type — loại biến động ghi vào thẻ kho. */
enum MovementType: string
{
    use HasOptions;

    case Import = 'import';
    case Export = 'export';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Import => 'Nhập kho',
            self::Export => 'Xuất kho',
            self::TransferIn => 'Nhận chuyển kho',
            self::TransferOut => 'Chuyển kho đi',
            self::Adjustment => 'Điều chỉnh kiểm kê',
        };
    }
}
