<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc danh mục sản phẩm (UC02.01): tìm theo tên/mô tả.
 */
class CategoryFilter extends Filter
{
    public const SORTABLE = ['id', 'name', 'created_at'];

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query->search($filters['keyword'] ?? null, ['name', 'description']);

        return static::sort($query, $filters, 'name');
    }
}
