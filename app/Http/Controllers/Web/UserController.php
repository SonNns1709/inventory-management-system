<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * Quản lý tài khoản người dùng — UC06.01, giao diện web. Chỉ Admin (BR06.01-1): route nằm trong nhóm middleware role:admin
 * và Policy UserPolicy::viewAny kiểm tra thêm một lần nữa. Toàn bộ thao tác (lọc, tạo, sửa, khóa, đặt lại mật khẩu)
 * nằm trong Livewire component App\Livewire\Users\UserIndex, gọi App\Services\UserService.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index');
    }
}
