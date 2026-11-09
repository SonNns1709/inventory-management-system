<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Nhật ký hoạt động — UC06.02 (bảng API, nhóm 7: #61). Chỉ Admin; chỉ đọc (BR06.02-6, BR06.02-7). */
class ActivityLogController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ActivityLogService $logs) {}

    /**
     * GET /api/activity-logs (#61) — mới nhất trước, có phân trang; `properties` chứa giá trị cũ/mới dạng JSON.
     * Tham số: from, to (YYYY-MM-DD), user_id, action, subject_type, keyword, per_page, page.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActivityLog::class);

        return $this->successPaginated(ActivityLogResource::class, $this->logs->search($request->query()));
    }
}
