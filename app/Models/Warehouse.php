<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kho / địa điểm lưu trữ (bảng `warehouses`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.4).
 */
class Warehouse extends Model
{
    use HasFactory, Searchable;

    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'address',
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
            'is_active' => 'boolean',
        ];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(Export::class, 'warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Export::class, 'dest_warehouse_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
