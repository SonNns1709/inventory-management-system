<?php

namespace App\Models\Concerns;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dùng cho các chứng từ có vòng đời draft -> confirmed | cancelled:
 * Import, Export, StockAdjustment (cột `status` đã được cast sang DocumentStatus).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR03.03-4, BR03.03-5: chỉ phiếu draft mới được sửa / xác nhận / hủy (kiểm tra bằng isDraft()); phiếu confirmed hoặc cancelled chỉ đọc.
 */
trait HasDocumentStatus
{
    public function isDraft(): bool
    {
        return $this->status === DocumentStatus::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this->status === DocumentStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === DocumentStatus::Cancelled;
    }

    /** Lọc theo trạng thái; bỏ qua nếu không truyền giá trị. */
    public function scopeWithStatus(Builder $query, DocumentStatus|string|null $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status instanceof DocumentStatus ? $status->value : $status);
    }
}
