<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc kho (UC02.03): tìm theo mã/tên/địa chỉ, lọc theo trạng thái hoạt động.
 */
class WarehouseFilter extends Filter
{
    public const SORTABLE = ['id', 'code', 'name', 'created_at'];

    protected static function extraRules(): array
    {
        return ['is_active' => 'nullable|boolean'];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['code', 'name', 'address'])
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']));

        return static::sort($query, $filters, 'code');
    }
}
