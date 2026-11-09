<?php

namespace App\Support;

/**
 * Định dạng số theo thói quen Việt Nam (dấu chấm ngăn cách hàng nghìn, dấu phẩy thập phân) cho các view Blade.
 * Không dùng NumberFormatter / ext-intl để khung chạy được trên mọi cấu hình PHP.
 */
final class Format
{
    /** 1234567.5 -> "1.234.568" (làm tròn tới $decimals chữ số thập phân). */
    public static function number(string|int|float|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    /** 1234567 -> "1.234.567 ₫". */
    public static function money(string|int|float|null $value, int $decimals = 0): string
    {
        return self::number($value, $decimals).' ₫';
    }
}
