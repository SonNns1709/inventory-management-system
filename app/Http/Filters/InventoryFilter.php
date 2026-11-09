<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc tồn kho (UC04.01, BR04.01-1): lọc theo sản phẩm hoặc kho; từ khóa tìm theo SKU/tên sản phẩm.
 */
class InventoryFilter extends Filter
{
    public const SORTABLE = ['product_id', 'warehouse_id', 'quantity', 'updated_at'];

    protected static function extraRules(): array
    {
        return ['product_id' => 'nullable|integer', 'warehouse_id' => 'nullable|integer'];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('product_id', $filters['product_id']))
            ->when(isset($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when(
                isset($filters['keyword']) && $filters['keyword'] !== '',
                fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->search($filters['keyword'], ['sku', 'name'])),
            );

        return static::sort($query, $filters, 'product_id');
    }
}
