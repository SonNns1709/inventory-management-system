<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Báo cáo Chương 1 §VII.1 (Bảng 3): hệ thống có đúng 2 vai trò.
 *  - Admin: Chủ cửa hàng / Quản lý kho — toàn quyền danh mục nền, người dùng, báo cáo.
 *  - Staff: Nhân viên kho — nghiệp vụ nhập/xuất/kiểm kê hằng ngày, chỉ xem danh mục nền.
 */
enum Role: string
{
    use HasOptions;

    case Admin = 'admin';
    case Staff = 'staff';

    /** Nhãn tiếng Việt hiển thị trên giao diện. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Staff => 'Nhân viên kho',
        };
    }
}
