<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chi tiết phiếu kiểm kê (bảng `stock_adjustment_items`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.11).
 */
class StockAdjustmentItem extends Model
{
    protected $table = 'stock_adjustment_items';

    protected $fillable = [
        'adjustment_id',
        'product_id',
        'system_qty',
        'counted_qty',
        'diff_qty',
        'reason',
        'unit_cost',
        'diff_value',
        'note',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'counted_qty' => 'integer',
            'diff_qty' => 'integer',
            'reason' => AdjustmentReason::class,
            'unit_cost' => 'decimal:4',
            'diff_value' => 'decimal:2',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
