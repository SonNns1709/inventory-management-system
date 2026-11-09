<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\HasDocumentStatus;
use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phiếu nhập kho (bảng `imports`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.6).
 */
class Import extends Model
{
    use HasDocumentStatus, Searchable;

    protected $table = 'imports';

    protected $fillable = [
        'code',
        'supplier_id',
        'warehouse_id',
        'import_date',
        'status',
        'total_amount',
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
            'import_date' => 'date',
            'status' => DocumentStatus::class,
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(ImportItem::class, 'import_id');
    }
}
