<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Validation\Rule;

/**
 * UC06.01 — Admin sửa tài khoản (PUT /api/users/{user} #58): chỉ name, role, phone — email không đổi, mật khẩu đổi
 * bằng chức năng đặt lại riêng. BR06.01-6 (không hạ quyền Admin cuối cùng) do UserService kiểm tra và trả 409.
 */
class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(Role::values())],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]+$/'],
        ];
    }
}
