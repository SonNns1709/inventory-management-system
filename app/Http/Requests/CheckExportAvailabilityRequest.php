<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Export;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** UC03.02 — kiểm tra tồn khả dụng trước khi xuất (POST /api/exports/check-availability #37). */
class CheckExportAvailabilityRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Export::class);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Kho xuất',
            'items' => 'Danh sách hàng xuất',
            'items.*.product_id' => 'Sản phẩm',
            'items.*.quantity' => 'Số lượng',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
