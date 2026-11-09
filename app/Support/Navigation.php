<?php

namespace App\Support;

use App\Models\User;

/**
 * Đọc config/navigation.php: lọc menu theo vai trò và tra siêu dữ liệu của một màn hình theo tên route.
 */
final class Navigation
{
    /**
     * Các nhóm menu mà người dùng được nhìn thấy (nhóm không còn mục nào thì bị bỏ).
     *
     * @return array<int, array{title: string, items: array<int, array<string, mixed>>}>
     */
    public static function forUser(?User $user): array
    {
        $role = $user?->role->value;

        if ($role === null) {
            return [];
        }

        $groups = [];

        foreach (config('navigation.groups', []) as $group) {
            $items = array_values(array_filter(
                $group['items'],
                fn (array $item): bool => in_array($role, $item['roles'], true),
            ));

            if ($items !== []) {
                $groups[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * Siêu dữ liệu của một màn hình theo tên route (null nếu không có trong cấu hình).
     *
     * @return array<string, mixed>|null
     */
    public static function find(?string $routeName): ?array
    {
        if ($routeName === null) {
            return null;
        }

        foreach (config('navigation.groups', []) as $group) {
            foreach ($group['items'] as $item) {
                if ($item['route'] === $routeName) {
                    return $item;
                }
            }
        }

        return null;
    }
}
