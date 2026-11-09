<?php

namespace App\Policies;

use App\Models\User;

/** UC06.02 — nhật ký hoạt động: chỉ Admin xem (BR06.02-1); không có quyền sửa / xóa nào (BR06.02-6, BR06.02-7). */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
