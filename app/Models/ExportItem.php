<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chi tiết phiếu xuất (bảng `export_items`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.9).
 */
class ExportItem extends Model
{
    protected $table = 'export_items';

    protected $fillable = [
        'export_id',
        'product_id',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:2',
        ];
    }

    public function export(): BelongsTo
    {
        return $this->belongsTo(Export::class, 'export_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
