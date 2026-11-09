<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * UC02.06 — sửa sản phẩm (PUT /api/products/{product} #25).
 * SKU là tùy chọn: chỉ được đổi khi sản phẩm chưa phát sinh giao dịch (BR02.06-3, BR02.06-4) —
 * quy tắc này cần truy vấn CSDL nên do ProductService::update() kiểm tra và trả 409.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'sku' => [
                'sometimes', 'required', 'string', 'max:30', 'regex:/^[A-Z0-9._\-]+$/',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
        ]);
    }
}
