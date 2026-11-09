<?php

namespace App\Models;

use App\Models\Concerns\HasStockLevels;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sản phẩm (mặt hàng quản lý theo SKU) (bảng `products`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.5).
 */
class Product extends Model
{
    use HasFactory, HasStockLevels, Searchable;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'unit',
        'price',
        'avg_cost',
        'min_stock_level',
        'description',
        'is_active',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'avg_cost' => 'decimal:4',
            'min_stock_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function importItems(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }

    public function exportItems(): HasMany
    {
        return $this->hasMany(ExportItem::class);
    }

    public function adjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
