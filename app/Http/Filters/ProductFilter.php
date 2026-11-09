<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc sản phẩm (UC02.04, BR02.04-2/3): tìm theo SKU hoặc tên; lọc theo danh mục, trạng thái, tồn dưới ngưỡng.
 */
class ProductFilter extends Filter
{
    public const SORTABLE = ['id', 'sku', 'name', 'price', 'avg_cost', 'created_at'];

    protected static function extraRules(): array
    {
        return ['category_id' => 'nullable|integer', 'is_active' => 'nullable|boolean', 'low_stock' => 'nullable|boolean'];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['sku', 'name'])
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('category_id', $filters['category_id']))
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']))
            // BR04.04-3: so min_stock_level với TỔNG tồn trên mọi kho (xem trait HasStockLevels)
            ->when(! empty($filters['low_stock']), fn (Builder $q) => $q->lowStock());

        return static::sort($query, $filters, 'name');
    }
}
