<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** UC01.01 — POST /api/auth/login (#1): email + password, device_name tùy chọn (đặt tên cho token). */
class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
