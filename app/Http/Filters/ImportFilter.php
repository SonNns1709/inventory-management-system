<?php

namespace App\Http\Filters;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Bộ lọc phiếu nhập kho (UC03.03, BR03.03-2): từ khóa, khoảng thời gian, trạng thái phiếu.
 */
class ImportFilter extends Filter
{
    public const SORTABLE = ['id', 'code', 'import_date', 'total_amount', 'status', 'created_at'];

    protected static function extraRules(): array
    {
        return static::dateRules() + [
            'status' => ['nullable', Rule::in(DocumentStatus::values())],
            'supplier_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['code', 'note'])
            ->withStatus($filters['status'] ?? null)
            ->when(isset($filters['supplier_id']), fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when(isset($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']));

        return static::sort(static::dateRange($query, $filters, 'import_date'), $filters, 'import_date', 'desc');
    }
}
