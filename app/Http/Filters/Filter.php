<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Khuôn chung của các bộ lọc danh sách (kế thừa cách làm của demo1: LopHocFilter / SinhVienFilter).
 *
 * Mỗi bộ lọc được dùng chung cho trang web (Livewire) và API nên hành vi luôn giống nhau:
 *   $filters = XFilter::valid($request->query());      // chỉ giữ lại tham số hợp lệ, tham số sai bị bỏ qua (không gây lỗi 500)
 *   $query   = XFilter::apply(X::query(), $filters);   // lọc + sắp xếp
 *   $query->paginate(XFilter::perPage($filters));
 */
abstract class Filter
{
    /** Các cột được phép sắp xếp (whitelist: không lấy thẳng tên cột từ URL để tránh SQL injection). */
    public const SORTABLE = ['id'];

    /**
     * Luật kiểm tra riêng của từng bộ lọc (cộng thêm vào các luật chung: keyword, sort, direction, per_page).
     *
     * @return array<string, mixed>
     */
    protected static function extraRules(): array
    {
        return [];
    }

    /** from / to (YYYY-MM-DD) cho các bộ lọc theo khoảng thời gian. */
    protected static function dateRules(): array
    {
        return [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ];
    }

    /**
     * Tham số trên URL do người dùng tự gõ nên có thể sai: valid() chỉ giữ lại tham số hợp lệ.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function valid(array $params): array
    {
        return Validator::make($params, [
            'keyword' => 'nullable|string|max:100',
            'sort' => ['nullable', Rule::in(static::SORTABLE)],
            'direction' => 'nullable|in:asc,desc',
            'per_page' => ['nullable', Rule::in(config('inventory.pagination.options', [10, 20, 50, 100]))],
        ] + static::extraRules())->valid();
    }

    /** Số dòng mỗi trang (mặc định lấy từ config/inventory.php). */
    public static function perPage(array $filters): int
    {
        return (int) ($filters['per_page'] ?? config('inventory.pagination.default', 20));
    }

    /**
     * Áp dụng bộ lọc và sắp xếp (đã qua valid()) vào câu truy vấn.
     *
     * @param  array<string, mixed>  $filters
     */
    abstract public static function apply(Builder $query, array $filters): Builder;

    /**
     * Sắp xếp theo cột được chọn (hoặc mặc định), kèm sắp xếp phụ theo id để thứ tự ổn định
     * khi nhiều dòng trùng giá trị (phân trang không lặp/sót dòng).
     */
    protected static function sort(Builder $query, array $filters, string $default = 'id', string $direction = 'asc'): Builder
    {
        return $query
            ->orderBy($filters['sort'] ?? $default, $filters['direction'] ?? $direction)
            ->orderBy('id', $filters['direction'] ?? $direction);
    }

    /** Lọc theo khoảng ngày [from, to] trên một cột ngày. */
    protected static function dateRange(Builder $query, array $filters, string $column): Builder
    {
        return $query
            ->when(isset($filters['from']), fn (Builder $q) => $q->whereDate($column, '>=', $filters['from']))
            ->when(isset($filters['to']), fn (Builder $q) => $q->whereDate($column, '<=', $filters['to']));
    }
}
