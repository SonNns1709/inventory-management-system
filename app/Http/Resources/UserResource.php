<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của tài khoản người dùng (bảng `users`) — không bao giờ có mật khẩu hay remember_token.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'status' => $this->status?->value,
            'phone' => $this->phone,
            'must_change_password' => (bool) $this->must_change_password,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
