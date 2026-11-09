<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Nhật ký hoạt động (audit log) (bảng `activity_logs`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.14).
 */
class ActivityLog extends Model
{
    use Searchable;

    /** Bảng chỉ ghi thêm: chỉ có created_at, không có updated_at. */
    public const UPDATED_AT = null;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'properties' => 'array',
        ];
    }

    /** Sổ chỉ ghi thêm (append-only): cấm sửa/xóa bản ghi đã ghi, đúng mô tả của báo cáo. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('activity_logs là sổ chỉ ghi thêm, không được sửa.'));
        static::deleting(fn () => throw new LogicException('activity_logs là sổ chỉ ghi thêm, không được xóa.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
