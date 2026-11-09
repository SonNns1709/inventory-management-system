<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc nhật ký hoạt động (UC06.02, BR06.02-2): thời gian, người dùng, loại hành động, đối tượng; luôn mới nhất trước (BR06.02-4).
 */
class ActivityLogFilter extends Filter
{
    public const SORTABLE = ['created_at', 'id'];

    protected static function extraRules(): array
    {
        return static::dateRules() + [
            'user_id' => 'nullable|integer',
            'action' => 'nullable|string|max:50',
            'subject_type' => 'nullable|string|max:50',
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['action', 'description'])
            ->when(isset($filters['user_id']), fn (Builder $q) => $q->where('user_id', $filters['user_id']))
            ->when(isset($filters['action']), fn (Builder $q) => $q->where('action', $filters['action']))
            ->when(isset($filters['subject_type']), fn (Builder $q) => $q->where('subject_type', $filters['subject_type']));

        // Nhật ký là sổ ghi theo thời gian nên luôn xếp mới nhất trước, không cho đổi cột sắp xếp
        return static::dateRange($query, $filters, 'created_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
