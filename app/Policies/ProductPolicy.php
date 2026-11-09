<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;

/**
 * UC02.04 – UC02.07 — sản phẩm (BR02.04-1, BR02.05-4, BR02.06-1).
 * Đọc: Admin và Nhân viên kho (cần danh sách để lập phiếu); thêm / sửa / xóa / ngừng: chỉ Admin (Bảng 9 — phân quyền).
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Staff);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
