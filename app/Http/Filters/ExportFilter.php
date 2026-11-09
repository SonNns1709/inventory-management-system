<?php

namespace App\Http\Filters;

use App\Enums\DocumentStatus;
use App\Enums\ExportReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Bộ lọc phiếu xuất kho (UC03.03, BR03.03-2): từ khóa, khoảng thời gian, trạng thái, lý do xuất.
 */
class ExportFilter extends Filter
{
    public const SORTABLE = ['id', 'code', 'export_date', 'total_cost', 'status', 'created_at'];

    protected static function extraRules(): array
    {
        return static::dateRules() + [
            'status' => ['nullable', Rule::in(DocumentStatus::values())],
            'reason' => ['nullable', Rule::in(ExportReason::values())],
            'warehouse_id' => 'nullable|integer',
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['code', 'note'])
            ->withStatus($filters['status'] ?? null)
            ->when(isset($filters['reason']), fn (Builder $q) => $q->where('reason', $filters['reason']))
            ->when(isset($filters['warehouse_id']), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']));

        return static::sort(static::dateRange($query, $filters, 'export_date'), $filters, 'export_date', 'desc');
    }
}
