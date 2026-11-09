<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Http\Filters\ActivityLogFilter;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Nhật ký hoạt động (audit log) — báo cáo Chương 3 §II.1.14 và UC06.02.
 *
 *  - record(): mọi Service gọi để ghi một dòng nhật ký (BR02.01-5, BR03.04-6, BR05.04-7, BR06.01-10...).
 *  - search(): Admin tra cứu, lọc theo thời gian / người dùng / hành động / đối tượng, mới nhất trước.
 *
 * Nhật ký chỉ được hệ thống ghi tự động, không có chức năng sửa hay xóa (BR06.02-6, BR06.02-7);
 * Model ActivityLog còn chặn cả update/delete ở tầng ORM.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR06.02-3: dữ liệu nhật ký truy vấn từ bảng activity_logs (search()).
 *  - BR06.02-8: chỉ tra cứu — không có thao tác sửa / xóa nhật ký.
 */
class ActivityLogService
{
    /**
     * Ghi một dòng nhật ký.
     *
     * @param  ActivityAction|string  $action  hành động, ví dụ ActivityAction::ImportConfirmed hoặc 'import_confirmed'
     * @param  Model|string|null  $subject  đối tượng tác động: một Model (lấy tên bảng + id) hoặc chỉ tên bảng
     * @param  array<string, mixed>|null  $properties  giá trị cũ/mới ({"old": {...}, "new": {...}}) hoặc dữ liệu bổ sung
     * @param  User|null  $user  người thực hiện; bỏ trống thì lấy tài khoản đang đăng nhập (đăng nhập sai email lạ: null)
     */
    public function record(
        ActivityAction|string $action,
        Model|string|null $subject = null,
        ?string $description = null,
        ?array $properties = null,
        ?User $user = null,
        ?string $ip = null,
    ): ActivityLog {
        $user ??= Auth::user();

        $subjectType = null;
        $subjectId = null;

        if ($subject instanceof Model) {
            $subjectType = $subject->getTable();
            $subjectId = $subject->getKey();
        } elseif (is_string($subject)) {
            $subjectType = $subject;
        }

        return ActivityLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action instanceof ActivityAction ? $action->value : $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description === null ? null : Str::limit($description, 255, ''),
            'properties' => $properties,
            'ip_address' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * UC06.02 — danh sách nhật ký phân trang, mới nhất trước (BR06.02-4).
     * Bộ lọc: from, to, user_id, action, subject_type, keyword (xem ActivityLogFilter).
     *
     * @param  array<string, mixed>  $params  tham số lọc thô lấy từ query string
     */
    public function search(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = ActivityLogFilter::valid($params);

        return ActivityLogFilter::apply(ActivityLog::query()->with('user'), $filters)
            ->paginate($perPage ?? ActivityLogFilter::perPage($filters));
    }
}
