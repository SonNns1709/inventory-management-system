<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * UC01.01 – UC01.03 trên giao diện web (phiên đăng nhập bằng session): đăng nhập, khóa tài khoản,
 * chống dò mật khẩu (5 lần / 15 phút), buộc đổi mật khẩu, đổi mật khẩu, đăng xuất.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_khach_vao_trang_can_dang_nhap_bi_chuyen_ve_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/categories')->assertRedirect('/login');
        $this->get('/products')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_trang_goc_chuyen_ve_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_form_dang_nhap_hien_thi_duoc(): void
    {
        $this->get('/login')->assertOk()->assertSee('Đăng nhập')->assertSee('name="password"', false);
    }

    public function test_da_dang_nhap_vao_trang_login_duoc_chuyen_ve_dashboard(): void
    {
        $this->dangNhapAdmin();

        $this->get('/login')->assertRedirect('/dashboard');
    }

    public function test_dang_nhap_dung_thong_tin_vao_dashboard_va_ghi_nhat_ky(): void
    {
        $user = User::factory()->admin()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_success', 'user_id' => $user->id]);
    }

    public function test_email_viet_hoa_va_co_khoang_trang_van_dang_nhap_duoc(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->post('/login', ['email' => '  A@Example.com ', 'password' => 'matkhau123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_dang_nhap_xong_quay_lai_trang_dang_vao_truoc_do(): void
    {
        User::factory()->admin()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->get('/categories')->assertRedirect('/login');

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])
            ->assertRedirect(url('/categories'));
    }

    public function test_sai_mat_khau_bao_loi_va_van_la_khach(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->from('/login')
            ->post('/login', ['email' => 'a@example.com', 'password' => 'sai-roi'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng']);

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed', 'user_id' => $user->id]);
    }

    public function test_email_khong_ton_tai_bao_cung_mot_thong_diep(): void
    {
        // Không cho người lạ biết email nào có trong hệ thống
        $this->from('/login')
            ->post('/login', ['email' => 'khong-co@example.com', 'password' => 'matkhau123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng']);
    }

    public function test_bo_trong_form_dang_nhap_bao_loi_tung_truong(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => '', 'password' => ''])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Vui lòng nhập email.', 'password' => 'Vui lòng nhập mật khẩu.']);
    }

    public function test_tai_khoan_bi_khoa_khong_dang_nhap_duoc(): void
    {
        User::factory()->locked()->create(['email' => 'k@example.com', 'password' => 'matkhau123']);

        $this->from('/login')
            ->post('/login', ['email' => 'k@example.com', 'password' => 'matkhau123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên']);

        $this->assertGuest();
    }

    public function test_tai_khoan_dang_dung_bi_khoa_thi_bi_dang_xuat_ngay(): void
    {
        // BR06.01-8: khóa tài khoản phải có hiệu lực ngay cả với phiên đang mở (middleware `active`)
        $this->dangNhapNhanVien(['status' => 'locked']);

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_sai_qua_5_lan_bi_chan_ke_ca_khi_nhap_dung_mat_khau(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        // BR01.01-2: tối đa 5 lần sai trong 15 phút cho mỗi cặp email + IP
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')
                ->post('/login', ['email' => 'a@example.com', 'password' => 'sai-roi'])
                ->assertSessionHasErrors('email');
        }

        $this->from('/login')
            ->post('/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('quá nhiều lần', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_het_15_phut_thi_duoc_dang_nhap_lai(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => 'a@example.com', 'password' => 'sai-roi']);
        }

        $this->luc('10:16');

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_tai_khoan_buoc_doi_mat_khau_duoc_chuyen_sang_trang_doi_mat_khau(): void
    {
        User::factory()->mustChangePassword()->create(['email' => 'n@example.com', 'password' => 'matkhau123']);

        $this->post('/login', ['email' => 'n@example.com', 'password' => 'matkhau123'])
            ->assertRedirect(route('profile.password.edit'));
    }

    public function test_chua_doi_mat_khau_thi_chi_vao_duoc_trang_doi_mat_khau(): void
    {
        $this->dangNhapNhanVien(['must_change_password' => true]);

        $this->get('/dashboard')->assertRedirect(route('profile.password.edit'));
        $this->get('/categories')->assertRedirect(route('profile.password.edit'));
        $this->get('/profile/password')->assertOk()->assertSee('Đổi mật khẩu');
    }

    public function test_doi_mat_khau_thanh_cong_bo_co_buoc_doi(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => 'matkhau123']);

        $this->actingAs($user)
            ->put('/profile/password', [
                'current_password' => 'matkhau123',
                'password' => 'matkhau456',
                'password_confirmation' => 'matkhau456',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('matkhau456', $user->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'password_changed', 'user_id' => $user->id]);
    }

    public function test_doi_mat_khau_sai_mat_khau_hien_tai(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->actingAs($user)->from('/profile/password')
            ->put('/profile/password', [
                'current_password' => 'sai-roi',
                'password' => 'matkhau456',
                'password_confirmation' => 'matkhau456',
            ])
            ->assertRedirect('/profile/password')
            ->assertSessionHasErrors(['current_password' => 'Mật khẩu hiện tại không đúng']);

        $this->assertTrue(Hash::check('matkhau123', $user->refresh()->password));
    }

    public function test_mat_khau_moi_khong_duoc_trung_mat_khau_cu(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        $this->actingAs($user)->from('/profile/password')
            ->put('/profile/password', [
                'current_password' => 'matkhau123',
                'password' => 'matkhau123',
                'password_confirmation' => 'matkhau123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_mat_khau_moi_phai_du_manh(): void
    {
        $user = User::factory()->create(['password' => 'matkhau123']);

        // BR01.03-2: ít nhất 8 ký tự, có chữ và số
        foreach (['ab12', 'chicochu', '12345678'] as $yeu) {
            $this->actingAs($user)->from('/profile/password')
                ->put('/profile/password', [
                    'current_password' => 'matkhau123',
                    'password' => $yeu,
                    'password_confirmation' => $yeu,
                ])
                ->assertSessionHasErrors('password');
        }
    }

    public function test_dang_xuat_huy_phien_va_ghi_nhat_ky(): void
    {
        $user = $this->dangNhapAdmin();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'logout', 'user_id' => $user->id]);
    }

    public function test_cap_nhat_ho_so_chi_doi_ten_va_so_dien_thoai(): void
    {
        $user = $this->dangNhapNhanVien(['email' => 'nv@example.com']);

        $this->put('/profile', ['name' => 'Tên Mới', 'phone' => '0912 345 678', 'email' => 'hack@example.com', 'role' => 'admin'])
            ->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertSame('Tên Mới', $user->name);
        $this->assertSame('nv@example.com', $user->email);
        $this->assertSame('staff', $user->role->value);
    }

    public function test_trang_ho_so_hien_thong_tin_tai_khoan(): void
    {
        $user = $this->dangNhapAdmin(['name' => 'Nguyễn Văn Quản']);

        $this->get('/profile')->assertOk()->assertSee('Nguyễn Văn Quản')->assertSee($user->email);
    }
}
