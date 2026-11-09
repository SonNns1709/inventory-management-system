<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * UC01.03 — đổi mật khẩu (PUT /api/auth/password #4, PUT /profile/password).
 *
 * Request chỉ kiểm tra hình thức và độ mạnh mật khẩu mới (BR01.03-2: >= 8 ký tự, có chữ và số, xem Password::defaults()
 * trong AppServiceProvider). Việc so khớp mật khẩu hiện tại (E1) và "mới không được trùng cũ" (BR01.03-1) do
 * AuthService::changePassword() xử lý để cả web lẫn API dùng chung.
 */
class ChangePasswordRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => 'Mật khẩu hiện tại', 'password' => 'Mật khẩu mới'];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
