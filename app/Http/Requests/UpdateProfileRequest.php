<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VietnameseMessages;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC01.04 — người dùng tự sửa hồ sơ của CHÍNH MÌNH (PUT /api/profile #6, PUT /profile).
 * BR01.04-1, BR01.04-2: chỉ họ tên và số điện thoại; email và vai trò không có trong luật nên không thể tự đổi
 * (kể cả khi ai đó gửi thêm role=admin — AuthService::updateProfile() cũng loại bỏ lần nữa).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR01.04-3: hồ sơ cá nhân chỉ gồm họ tên và số điện thoại; đổi email / vai trò là việc của Admin qua UC06.01 (UserService::update()).
 */
class UpdateProfileRequest extends FormRequest
{
    use VietnameseMessages;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]+$/'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Họ tên', 'phone' => 'Số điện thoại'];
    }

    public function messages(): array
    {
        return $this->commonMessages();
    }
}
