<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Giao diện đăng nhập hai cột (layouts/auth + auth/login). Luồng đăng nhập / khóa tài khoản đã có test ở AuthTest và ApiAuthTest.
 */
class WebLoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_trang_dang_nhap_co_cot_gioi_thieu_va_dong_nhac_khoa_tam_thoi(): void
    {
        $max = config('inventory.auth.login_max_attempts');
        $phut = config('inventory.auth.login_decay_minutes');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Hệ thống Quản lý hàng tồn kho')
            ->assertSee('Cảnh báo tồn thấp theo ngưỡng của từng sản phẩm')
            ->assertSee("Đăng nhập sai {$max} lần sẽ bị tạm khóa {$phut} phút")
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertDontSee('admin@inventory.test'); // gợi ý tài khoản mẫu chỉ hiện ở môi trường local
    }

    public function test_dang_nhap_sai_giu_lai_email_da_go_va_hien_loi(): void
    {
        $this->followingRedirects()
            ->from('/login')
            ->post('/login', ['email' => 'khongco@example.com', 'password' => 'sai-mat-khau'])
            ->assertOk()
            ->assertSee('value="khongco@example.com"', false)
            ->assertSee('field-error', false);
    }

    public function test_trang_loi_van_dung_bo_cuc_guest_cu(): void
    {
        $this->get('/trang-khong-ton-tai')->assertNotFound();
    }
}
