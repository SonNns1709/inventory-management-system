<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC04.03 — lập phiếu kiểm kê, trạng thái draft kèm snapshot system_qty (POST /api/adjustments #47).
 *
 * Báo cáo chỉ ghi "warehouse_id, phạm vi kiểm kê, note" nên khung đề xuất cách diễn đạt phạm vi kiểm kê:
 *   scope = all (mặc định: mọi sản phẩm có tồn ở kho) | category (kèm category_id) | products (kèm product_ids[]).
 * Nhóm có thể đổi lại khi chốt đặc tả UC04.03.
 */
class StoreStockAdjustmentRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', StockAdjustment::class);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'adjustment_date' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', Rule::in(['all', 'category', 'products'])],
            'category_id' => ['required_if:scope,category', 'nullable', 'integer', 'exists:categories,id'],
            'product_ids' => ['required_if:scope,products', 'nullable', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Kho kiểm kê',
            'adjustment_date' => 'Ngày kiểm kê',
            'note' => 'Ghi chú',
            'scope' => 'Phạm vi kiểm kê',
            'category_id' => 'Danh mục',
            'product_ids' => 'Danh sách sản phẩm',
            'product_ids.*' => 'Sản phẩm',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
