<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\ExportReason;
use App\Models\Concerns\HasDocumentStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phiếu xuất kho (bán hàng hoặc chuyển kho) (bảng `exports`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.8).
 */
class Export extends Model
{
    use HasDocumentStatus, Searchable;

    protected $table = 'exports';

    protected $fillable = [
        'code',
        'warehouse_id',
        'reason',
        'dest_warehouse_id',
        'export_date',
        'status',
        'total_cost',
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
            'reason' => ExportReason::class,
            'export_date' => 'date',
            'status' => DocumentStatus::class,
            'total_cost' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function destWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dest_warehouse_id');
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
        return $this->hasMany(ExportItem::class, 'export_id');
    }
}
