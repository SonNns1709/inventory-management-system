<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Các layout gọi @vite(); test không cần build CSS/JS nên tắt Vite (job "test" của CI không cài Node).
        $this->withoutVite();

        // Cố định "bây giờ" là 10:00 sáng thứ Hai 02/11/2026 để kết quả test (số chứng từ PNyyyymmdd-nnnn,
        // "phiếu nhập hôm nay" của Dashboard, bộ đếm đăng nhập sai) không phụ thuộc giờ chạy.
        // Test nào cần giờ khác thì gọi $this->luc('23:59').
        $this->luc('10:00');
    }

    /** Tạo một tài khoản có vai trò cho trước (mặc định là Nhân viên kho, mật khẩu "password"). */
    protected function taoUser(Role $role = Role::Staff, array $thuocTinh = []): User
    {
        return User::factory()->create(['role' => $role] + $thuocTinh);
    }

    /** Tạo Admin rồi đăng nhập bằng phiên web (actingAs); trả về tài khoản đó. */
    protected function dangNhapAdmin(array $thuocTinh = []): User
    {
        return tap($this->taoUser(Role::Admin, $thuocTinh), fn (User $user) => $this->actingAs($user));
    }

    /** Tạo Nhân viên kho rồi đăng nhập bằng phiên web. */
    protected function dangNhapNhanVien(array $thuocTinh = []): User
    {
        return tap($this->taoUser(Role::Staff, $thuocTinh), fn (User $user) => $this->actingAs($user));
    }

    /** Tạo Admin rồi xác thực API bằng token Sanctum giả lập (không cần gọi /api/auth/login). */
    protected function apiAdmin(array $thuocTinh = []): User
    {
        return tap($this->taoUser(Role::Admin, $thuocTinh), fn (User $user) => Sanctum::actingAs($user));
    }

    /** Tạo Nhân viên kho rồi xác thực API bằng token Sanctum giả lập. */
    protected function apiNhanVien(array $thuocTinh = []): User
    {
        return tap($this->taoUser(Role::Staff, $thuocTinh), fn (User $user) => Sanctum::actingAs($user));
    }

    /**
     * Guard của Sanctum nhớ người dùng đã xác thực trong suốt một test. Gọi hàm này giữa hai request dùng token KHÁC NHAU
     * (hoặc cùng một token vừa bị thu hồi) để request sau được xác thực lại từ đầu.
     */
    protected function quenXacThucApi(): void
    {
        $this->app['auth']->forgetGuards();
    }

    /** Đặt giờ hiện tại (theo múi giờ ứng dụng) trong ngày 02/11/2026, ví dụ $this->luc('17:31'). */
    protected function luc(string $gio): void
    {
        Carbon::setTestNow(Carbon::parse("2026-11-02 {$gio}:00", config('app.timezone')));
    }
}
