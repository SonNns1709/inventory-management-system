<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC03.01 — lập phiếu nhập kho, trạng thái draft (POST /api/imports #31).
 *
 * BR03.01-1: chỉ Nhân viên kho (Policy -> User::canOperateWarehouse()); BR03.01-2: nhà cung cấp và kho nhập hợp lệ
 * (còn hoạt động); mỗi dòng: sản phẩm đang kinh doanh (UC02.07: sản phẩm ngừng kinh doanh bị ẩn khỏi phiếu mới),
 * số lượng nguyên dương (CHECK quantity > 0 trong CSDL), đơn giá nhập >= 0.
 */
class StoreImportRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Import::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'import_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999999.9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'Nhà cung cấp',
            'warehouse_id' => 'Kho nhập',
            'import_date' => 'Ngày nhập',
            'note' => 'Ghi chú',
            'items' => 'Danh sách hàng nhập',
            'items.*.product_id' => 'Sản phẩm',
            'items.*.quantity' => 'Số lượng',
            'items.*.unit_cost' => 'Đơn giá nhập',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages() + [
            'items.required' => 'Phiếu nhập phải có ít nhất một dòng hàng.',
            'items.min' => 'Phiếu nhập phải có ít nhất một dòng hàng.',
        ];
    }
}
