<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Bộ lọc nhà cung cấp (UC02.02): tìm theo tên/người liên hệ/SĐT/email/mã số thuế, lọc theo trạng thái hợp tác.
 */
class SupplierFilter extends Filter
{
    public const SORTABLE = ['id', 'name', 'created_at'];

    protected static function extraRules(): array
    {
        return ['is_active' => 'nullable|boolean'];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['name', 'contact_person', 'phone', 'email', 'tax_code'])
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active']));

        return static::sort($query, $filters, 'name');
    }
}
