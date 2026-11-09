<?php

namespace App\Http\Requests;

use App\Enums\AdjustmentReason;
use App\Http\Requests\Concerns\VietnameseMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC04.03 — nhập số đếm thực tế vào phiếu nháp (PUT /api/adjustments/{adjustment} #48).
 * Mỗi dòng: product_id, counted_qty (>= 0), reason, note. BR04.03-5: nếu diff_qty ≠ 0 thì bắt buộc có lý do —
 * diff_qty = counted_qty − system_qty cần system_qty (snapshot trong CSDL) nên StockAdjustmentService::saveCounts()
 * mới kiểm tra điều kiện này (trả 422 kèm tên dòng lỗi); ở đây chỉ kiểm tra hình thức.
 */
class UpdateStockAdjustmentRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('adjustment'));
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.counted_qty' => ['required', 'integer', 'min:0'],
            'items.*.reason' => ['nullable', Rule::in(AdjustmentReason::values())],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'note' => 'Ghi chú',
            'items' => 'Danh sách dòng kiểm kê',
            'items.*.product_id' => 'Sản phẩm',
            'items.*.counted_qty' => 'Số lượng đếm thực tế',
            'items.*.reason' => 'Lý do chênh lệch',
            'items.*.note' => 'Ghi chú dòng',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
