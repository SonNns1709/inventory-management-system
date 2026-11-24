<?php

namespace App\Http\Requests;

use App\Models\Product;

/**
 * UC02.06 — sửa sản phẩm (PUT /api/products/{product} #25).
 * SKU là tùy chọn; StoreProductRequest kiểm tra unique; đổi SKU khi đã phát sinh giao dịch
 * được chuyển cho ProductService xử lý và trả HTTP 409.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return static::rulesFor($product);
    }
}
