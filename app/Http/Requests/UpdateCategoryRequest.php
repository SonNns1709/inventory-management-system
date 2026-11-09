<?php

namespace App\Http\Requests;

use App\Models\Category;

/** UC02.01 — sửa danh mục (PUT /api/categories/{category} #9). Khác lúc thêm: quyền sửa đúng bản ghi, tên được trùng chính nó. */
class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function authorize(): bool
    {
        /** @var Category $category route model binding */
        $category = $this->route('category');

        return $this->user()->can('update', $category);
    }

    public function rules(): array
    {
        return static::rulesFor($this->route('category'));
    }
}
