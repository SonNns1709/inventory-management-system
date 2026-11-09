<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Truy vấn tồn kho của sản phẩm — dùng cho Dashboard (UC05.01), Cảnh báo tồn thấp (UC04.04),
 * danh sách sản phẩm (UC02.04: "tổng tồn và đánh dấu sản phẩm dưới ngưỡng").
 *
 * Quy tắc cốt lõi (BR04.04-3, BR04.04-4): so sánh min_stock_level với TỔNG tồn của sản phẩm
 * trên TẤT CẢ các kho (không phải tồn riêng từng kho); sản phẩm chưa có dòng tồn nào coi như tồn 0.
 * SQL thuần, chạy giống nhau trên MySQL và SQLite (khi chạy test).
 */
trait HasStockLevels
{
    /** Biểu thức SQL: tổng tồn của sản phẩm trên mọi kho (0 nếu chưa có dòng tồn). */
    public static function totalStockSql(): string
    {
        return '(SELECT COALESCE(SUM(inventories.quantity), 0) FROM inventories WHERE inventories.product_id = products.id)';
    }

    /** Thêm cột ảo `total_stock` (tổng tồn mọi kho) vào kết quả. */
    public function scopeWithTotalStock(Builder $query): Builder
    {
        return $query->withSum('inventories as total_stock', 'quantity');
    }

    /** Chỉ lấy sản phẩm có tổng tồn < min_stock_level. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw(static::totalStockSql().' < products.min_stock_level');
    }

    /** Sắp xếp giảm dần theo mức thiếu = min_stock_level − tổng tồn (BR04.04-5, BR04.04-6). */
    public function scopeOrderByShortage(Builder $query): Builder
    {
        return $query->orderByRaw('(products.min_stock_level - '.static::totalStockSql().') DESC')
            ->orderBy('products.id');
    }
}
