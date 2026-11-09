<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\HasDocumentStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phiếu kiểm kê và điều chỉnh tồn kho (bảng `stock_adjustments`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.10).
 */
class StockAdjustment extends Model
{
    use HasDocumentStatus, Searchable;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'code',
        'warehouse_id',
        'adjustment_date',
        'status',
        'note',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'status' => DocumentStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }
}
