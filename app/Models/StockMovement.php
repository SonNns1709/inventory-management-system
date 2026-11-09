<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Sổ biến động tồn kho (thẻ kho), chỉ ghi thêm, không sửa (bảng `stock_movements`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.13).
 */
class StockMovement extends Model
{
    /** Bảng chỉ ghi thêm: chỉ có created_at, không có updated_at. */
    public const UPDATED_AT = null;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'reference_type',
        'reference_id',
        'qty_change',
        'balance_after',
        'unit_cost',
        'avg_cost_after',
        'created_by',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'reference_id' => 'integer',
            'qty_change' => 'integer',
            'balance_after' => 'integer',
            'unit_cost' => 'decimal:4',
            'avg_cost_after' => 'decimal:4',
        ];
    }

    /** Sổ chỉ ghi thêm (append-only): cấm sửa/xóa bản ghi đã ghi, đúng mô tả của báo cáo. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('stock_movements là sổ chỉ ghi thêm, không được sửa.'));
        static::deleting(fn () => throw new LogicException('stock_movements là sổ chỉ ghi thêm, không được xóa.'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
