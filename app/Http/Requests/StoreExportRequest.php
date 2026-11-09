<?php

namespace App\Http\Requests;

use App\Enums\ExportReason;
use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Export;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC03.02 — lập phiếu xuất kho, trạng thái draft (POST /api/exports #38).
 *
 * reason = sale (bán hàng) hoặc transfer (chuyển kho; khi đó bắt buộc dest_warehouse_id, khác kho xuất).
 * BR03.02-2: kho xuất đang hoạt động. Việc kiểm tra "số lượng xuất không vượt tồn khả dụng" (BR03.02-3) phụ thuộc
 * tồn tại thời điểm xác nhận nên do ExportService::confirm() kiểm tra lại trong transaction (draft chưa trừ tồn).
 */
class StoreExportRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Export::class);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'reason' => ['required', Rule::in(ExportReason::values())],
            'dest_warehouse_id' => [
                'nullable', 'required_if:reason,'.ExportReason::Transfer->value, 'integer', 'different:warehouse_id',
                Rule::exists('warehouses', 'id')->where('is_active', true),
            ],
            'export_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Kho xuất',
            'reason' => 'Lý do xuất',
            'dest_warehouse_id' => 'Kho nhận',
            'export_date' => 'Ngày xuất',
            'note' => 'Ghi chú',
            'items' => 'Danh sách hàng xuất',
            'items.*.product_id' => 'Sản phẩm',
            'items.*.quantity' => 'Số lượng',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages() + [
            'dest_warehouse_id.required_if' => 'Chuyển kho phải chọn kho nhận.',
            'dest_warehouse_id.different' => 'Kho nhận phải khác kho xuất.',
            'items.required' => 'Phiếu xuất phải có ít nhất một dòng hàng.',
            'items.min' => 'Phiếu xuất phải có ít nhất một dòng hàng.',
        ];
    }

    /** Khi bán hàng, kho nhận không có ý nghĩa: bỏ đi để không lưu nhầm vào exports.dest_warehouse_id. */
    protected function passedValidation(): void
    {
        if ($this->input('reason') === ExportReason::Sale->value) {
            $this->merge(['dest_warehouse_id' => null]);
        }
    }
}
