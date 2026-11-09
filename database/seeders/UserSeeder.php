<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tài khoản mẫu cho hai vai trò của hệ thống (báo cáo Chương 1 §VII.1 — Bảng 3) và hai trường hợp đặc biệt của UC01.01.
 *
 * | Email                | Mật khẩu   | Vai trò        | Ghi chú                                                    |
 * |----------------------|------------|----------------|------------------------------------------------------------|
 * | admin@inventory.test | Admin@123  | Admin          |                                                            |
 * | staff@inventory.test | Staff@123  | Nhân viên kho  |                                                            |
 * | locked@inventory.test| Staff@123  | Nhân viên kho  | Đã khóa -> "Tài khoản đã bị khóa" (Exception Flow E2)     |
 * | newbie@inventory.test| Staff@123  | Nhân viên kho  | Buộc đổi mật khẩu ở lần đăng nhập đầu (Alternative A1)    |
 *
 * CHỈ DÙNG CHO PHÁT TRIỂN / DEMO: khi triển khai thật hãy đổi mật khẩu hoặc xóa các tài khoản này.
 * Mật khẩu thỏa BR01.03-2 (>= 8 ký tự, có chữ và số) và được băm tự động nhờ cast `hashed` của User.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin@inventory.test', 'Quản trị viên', 'Admin@123', Role::Admin, UserStatus::Active, false, '0901000001'],
            ['staff@inventory.test', 'Nhân viên kho', 'Staff@123', Role::Staff, UserStatus::Active, false, '0901000002'],
            ['locked@inventory.test', 'Tài khoản bị khóa', 'Staff@123', Role::Staff, UserStatus::Locked, false, '0901000003'],
            ['newbie@inventory.test', 'Nhân viên mới', 'Staff@123', Role::Staff, UserStatus::Active, true, '0901000004'],
        ];

        foreach ($accounts as [$email, $name, $password, $role, $status, $mustChange, $phone]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'role' => $role,
                    'status' => $status,
                    'phone' => $phone,
                    'must_change_password' => $mustChange,
                ],
            );
        }
    }
}
