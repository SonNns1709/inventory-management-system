<?php

namespace App\Policies;

use App\Models\User;

/**
 * UC06.01 — quản lý tài khoản: chỉ Admin (BR06.01-1). Quy tắc "luôn còn ít nhất một Admin hoạt động" (BR06.01-6) và
 * "Admin không tự khóa mình" (BR06.01-7) cần dữ liệu trong CSDL nên do UserService kiểm tra và trả 409.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }
}
