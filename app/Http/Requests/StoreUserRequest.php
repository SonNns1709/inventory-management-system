<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Http\Requests\Concerns\VietnameseMessages;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * UC06.01 — Admin tạo tài khoản (POST /api/users #57): họ tên, email, vai trò, số điện thoại và MẬT KHẨU TẠM
 * (BR06.01-3). BR06.01-4: email duy nhất. Tài khoản mới luôn có must_change_password = true (đặt ở UserService).
 */
class StoreUserRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }

        foreach (['name', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(Role::values())],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]+$/'],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Họ tên', 'email' => 'Email', 'role' => 'Vai trò', 'phone' => 'Số điện thoại', 'password' => 'Mật khẩu tạm'];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
