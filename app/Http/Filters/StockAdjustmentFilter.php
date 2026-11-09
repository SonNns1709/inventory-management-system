<?php

namespace App\Http\Filters;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Bộ lọc phiếu kiểm kê (UC04.03): từ khóa, khoảng thời gian, trạng thái, kho.
 */
class StockAdjustmentFilter extends Filter
{
    public const SORTABLE = ['id', 'code', 'adjustment_date', 'status', 'created_at'];

    protected static function extraRules(): array
    {
        return static::dateRules() + [
            'status' => ['nullable', Rule::in(DocumentStatus::values())],
            'warehouse_id' => 'nullable|integer',
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['code', 'note'])
            ->withStatus($filters['status'] ?? null)
            ->when(isset($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']));

        return static::sort(static::dateRange($query, $filters, 'adjustment_date'), $filters, 'adjustment_date', 'desc');
    }
}
