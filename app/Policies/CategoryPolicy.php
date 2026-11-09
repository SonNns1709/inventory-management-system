<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;

/**
 * UC02.01 — danh mục sản phẩm (BR02.01-4).
 * Đọc: Admin và Nhân viên kho (cần danh sách để lập phiếu); thêm / sửa / xóa / ngừng: chỉ Admin (Bảng 9 — phân quyền).
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Staff);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
