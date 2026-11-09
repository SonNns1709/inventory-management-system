<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tài khoản mẫu cho test và dữ liệu thử (mật khẩu mặc định: "password").
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Băm một lần rồi dùng lại cho mọi tài khoản trong cùng một lần chạy (bcrypt chậm). */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Staff,
            'status' => UserStatus::Active,
            'phone' => fake()->numerify('09########'),
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    public function staff(): static
    {
        return $this->state(fn () => ['role' => Role::Staff]);
    }

    /** Tài khoản bị khóa (users.status = 'locked') — không đăng nhập được (UC01.01 Exception E2). */
    public function locked(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Locked]);
    }

    /** Tài khoản mới tạo / vừa được đặt lại mật khẩu: bắt buộc đổi mật khẩu (UC01.01 Alternative A1). */
    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }
}
