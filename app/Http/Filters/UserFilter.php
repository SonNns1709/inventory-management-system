<?php

namespace App\Http\Filters;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Bộ lọc tài khoản người dùng (UC06.01): tìm theo tên/email/SĐT, lọc theo vai trò và trạng thái.
 */
class UserFilter extends Filter
{
    public const SORTABLE = ['id', 'name', 'email', 'created_at', 'last_login_at'];

    protected static function extraRules(): array
    {
        return [
            'role' => ['nullable', Rule::in(Role::values())],
            'status' => ['nullable', Rule::in(UserStatus::values())],
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $query = $query
            ->search($filters['keyword'] ?? null, ['name', 'email', 'phone'])
            ->when(isset($filters['role']), fn (Builder $q) => $q->where('role', $filters['role']))
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));

        return static::sort($query, $filters, 'name');
    }
}
