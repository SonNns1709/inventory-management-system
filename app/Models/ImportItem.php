<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chi tiết phiếu nhập (bảng `import_items`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.7).
 */
class ImportItem extends Model
{
    protected $table = 'import_items';

    protected $fillable = [
        'import_id',
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

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
