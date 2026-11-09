<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC02.02 — thêm nhà cung cấp (POST /api/suppliers #12). BR02.02-1: tên duy nhất; BR02.02-2: thông tin hợp lệ;
 * BR02.02-6: chỉ Admin. Trạng thái hợp tác (is_active) không nhận ở đây — đổi bằng PATCH /suppliers/{id}/toggle.
 */
class StoreSupplierRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', Supplier::class);
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'contact_person', 'phone', 'tax_code'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }

        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:suppliers,name'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]+$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên nhà cung cấp',
            'contact_person' => 'Người liên hệ',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ',
            'tax_code' => 'Mã số thuế',
        ];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
