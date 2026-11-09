<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nhà cung cấp (bảng `suppliers`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.3).
 */
class Supplier extends Model
{
    use HasFactory, Searchable;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_code',
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

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }
}
