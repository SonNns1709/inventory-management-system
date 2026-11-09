<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Supplier;
use App\Models\User;

/**
 * UC02.02 — nhà cung cấp (BR02.02-6).
 * Đọc: Admin và Nhân viên kho (cần danh sách để lập phiếu); thêm / sửa / xóa / ngừng: chỉ Admin (Bảng 9 — phân quyền).
 */
class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Staff);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }
}
