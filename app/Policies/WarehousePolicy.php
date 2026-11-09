<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Warehouse;

/**
 * UC02.03 — kho (BR02.03-2).
 * Đọc: Admin và Nhân viên kho (cần danh sách để lập phiếu); thêm / sửa / xóa / ngừng: chỉ Admin (Bảng 9 — phân quyền).
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Staff);
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin();
    }
}
