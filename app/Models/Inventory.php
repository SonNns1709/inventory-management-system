<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tồn kho hiện tại theo sản phẩm và kho (bảng `inventories`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.12).
 */
class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
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
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
