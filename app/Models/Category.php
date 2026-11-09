<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Danh mục (nhóm) sản phẩm (bảng `categories`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.2).
 */
class Category extends Model
{
    use HasFactory, Searchable;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
