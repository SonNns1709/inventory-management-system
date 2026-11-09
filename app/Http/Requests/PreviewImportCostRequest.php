<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC03.01 Alternative Flow A3 — xem trước giá vốn bình quân mới (POST /api/imports/preview-cost #30).
 * Tên trường camelCase (qtyIn, unitCost) và khóa kết quả `newAvgCost` đúng như bảng API của báo cáo.
 */
class PreviewImportCostRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Import::class);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qtyIn' => ['required', 'integer', 'min:1'],
            'unitCost' => ['required', 'numeric', 'min:0', 'max:99999999999.9999'],
        ];
    }

    public function attributes(): array
    {
        return ['product_id' => 'Sản phẩm', 'qtyIn' => 'Số lượng nhập', 'unitCost' => 'Đơn giá nhập'];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
