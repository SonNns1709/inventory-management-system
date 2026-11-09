<?php

namespace App\Enums\Concerns;

/**
 * Tiện ích dùng chung cho các enum có phương thức label() (nhãn tiếng Việt hiển thị trên giao diện).
 */
trait HasOptions
{
    /**
     * Danh sách các giá trị hợp lệ, ví dụ ['admin', 'staff'] (dùng cho Rule::in).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Danh sách "giá trị => nhãn" dùng cho thẻ <select>.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
