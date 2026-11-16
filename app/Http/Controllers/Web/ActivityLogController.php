<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;

/**
 * Nhật ký hoạt động — UC06.02, giao diện web. Chỉ Admin xem (BR06.02-1): route nằm trong nhóm middleware role:admin
 * và Policy ActivityLogPolicy::viewAny kiểm tra thêm một lần nữa (phòng thủ nhiều lớp, giống CategoryController).
 * Toàn bộ thao tác lọc / phân trang nằm trong Livewire component App\Livewire\ActivityLogs\ActivityLogIndex.
 */
class ActivityLogController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        return view('activity-logs.index');
    }
}
