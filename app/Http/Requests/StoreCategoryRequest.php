<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC02.01 — thêm danh mục (POST /api/categories #8). MẪU THAM CHIẾU cho các Form Request còn lại.
 *
 *   authorize()            -> Policy quyết định ai được tạo (403 nếu không có quyền, chạy TRƯỚC khi validate);
 *   prepareForValidation() -> chuẩn hóa dữ liệu (cắt khoảng trắng);
 *   rules()                -> BR02.01-1 tên không trống, BR02.01-2 tên duy nhất (CSDL còn có UNIQUE làm lớp bảo vệ thứ hai).
 */
class StoreCategoryRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->name)) {
            $this->merge(['name' => trim($this->name)]);
        }
    }

    public function rules(): array
    {
        return static::rulesFor();
    }

    /**
     * Luật kiểm tra dùng chung cho Form Request (API) và Livewire component (web), để hai giao diện không bao giờ lệch nhau.
     * Truyền danh mục đang sửa để tên được trùng chính nó (BR02.01-2); bỏ trống khi thêm mới.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?Category $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Tên danh mục', 'description' => 'Mô tả'];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
