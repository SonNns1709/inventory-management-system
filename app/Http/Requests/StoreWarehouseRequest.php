<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC02.03 — thêm kho (POST /api/warehouses #17). BR02.03-1: mã kho hợp lệ và duy nhất (luôn viết hoa);
 * BR02.03-2: chỉ Admin. Kho mới mặc định đang hoạt động; ngừng/kích hoạt bằng PATCH /warehouses/{id}/toggle.
 */
class StoreWarehouseRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Warehouse::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->code)) {
            $this->merge(['code' => strtoupper(trim($this->code))]);
        }

        if (is_string($this->name)) {
            $this->merge(['name' => trim($this->name)]);
        }
    }

    public function rules(): array
    {
        return static::rulesFor();
    }

    /**
     * Luật dùng chung cho Form Request (API) và Livewire; mã hiện tại được giữ nguyên khi sửa.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?Warehouse $warehouse = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('warehouses', 'code')->ignore($warehouse)],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'Mã kho', 'name' => 'Tên kho', 'address' => 'Địa chỉ'];
    }

    public function messages(): array
    {
        return $this->commonMessages() + [
            'code.regex' => 'Mã kho chỉ gồm chữ cái không dấu, chữ số, dấu gạch ngang và gạch dưới.',
        ];
    }
}
