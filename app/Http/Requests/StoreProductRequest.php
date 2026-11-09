<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC02.05 — thêm sản phẩm (POST /api/products #23).
 *
 * BR02.05-1: SKU duy nhất toàn hệ thống (kiểm tra ở đây + UNIQUE trong CSDL); BR02.05-4: chỉ Admin;
 * BR02.05-5: đủ thông tin bắt buộc, đúng định dạng.
 * BR02.05-2/3: avg_cost và is_active KHÔNG nhận từ client — sản phẩm mới luôn avg_cost = 0, is_active = true;
 * giá vốn chỉ do hệ thống tính khi xác nhận phiếu nhập đầu tiên (UC03.01).
 */
class StoreProductRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->sku)) {
            $this->merge(['sku' => strtoupper(trim($this->sku))]);
        }

        foreach (['name', 'unit'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9._\-]+$/', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => 'Mã SKU',
            'name' => 'Tên sản phẩm',
            'category_id' => 'Danh mục',
            'unit' => 'Đơn vị tính',
            'price' => 'Giá bán',
            'min_stock_level' => 'Ngưỡng cảnh báo tồn kho',
            'description' => 'Mô tả',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages() + [
            'sku.regex' => 'SKU chỉ gồm chữ cái không dấu, chữ số và các ký tự . _ -',
        ];
    }
}
