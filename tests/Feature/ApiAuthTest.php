<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Nhóm API 1 — Auth & Hồ sơ cá nhân (bảng API #1 – #6): đăng nhập lấy token Sanctum, đăng xuất, đổi mật khẩu,
 * hồ sơ; khuôn phản hồi { "data": ... } / { "message", "errors" } và các mã 401 / 403 / 422 / 429.
 */
class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_dang_nhap_thanh_cong_tra_token_va_thong_tin_nguoi_dung(): void
    {
        $user = User::factory()->admin()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'matkhau123', 'device_name' => 'postman'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'must_change_password', 'user' => ['id', 'name', 'email', 'role']]])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.must_change_password', false)
            ->assertJsonPath('data.user.email', 'a@example.com')
            ->assertJsonPath('data.user.role', 'admin');

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'postman']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_success', 'user_id' => $user->id]);
    }

    public function test_phan_hoi_dang_nhap_khong_lo_mat_khau(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $response = $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])->assertOk();

        $this->assertStringNotContainsString('"password"', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function test_sai_mat_khau_tra_422_kem_loi_o_truong_email(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'sai-roi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Email hoặc mật khẩu không đúng');
    }

    public function test_thieu_truong_bat_buoc_tra_422(): void
    {
        $this->postJson('/api/auth/login', [])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_tai_khoan_bi_khoa_tra_422_voi_thong_diep_rieng(): void
    {
        User::factory()->locked()->create(['email' => 'k@example.com', 'password' => 'matkhau123']);

        $this->postJson('/api/auth/login', ['email' => 'k@example.com', 'password' => 'matkhau123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên');
    }

    public function test_sai_qua_5_lan_tra_429_kem_retry_after(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'sai-roi'])->assertStatus(422);
        }

        // Từ lần thứ 6, kể cả nhập ĐÚNG mật khẩu cũng bị chặn (BR01.01-2)
        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'matkhau123'])
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['message']);
    }

    public function test_bo_dem_dang_nhap_sai_tinh_rieng_theo_tung_email(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'matkhau123']);
        User::factory()->create(['email' => 'b@example.com', 'password' => 'matkhau123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'sai-roi']);
        }

        $this->postJson('/api/auth/login', ['email' => 'b@example.com', 'password' => 'matkhau123'])->assertOk();
    }

    public function test_chua_dang_nhap_tra_401_dang_json_ke_ca_khi_khong_gui_accept(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated']);

        // Báo cáo Chương 3 §III: mọi endpoint dưới /api luôn trả JSON — không bị chuyển hướng sang /login
        $this->get('/api/auth/me')->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated']);
    }

    public function test_token_sai_tra_401(): void
    {
        $this->withToken('khong-phai-token-that')->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_tra_thong_tin_nguoi_dung_theo_token(): void
    {
        $user = $this->taoUser(Role::Staff, ['name' => 'Nguyễn Văn A']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.name', 'Nguyễn Văn A')
            ->assertJsonPath('data.role', 'staff')
            ->assertJsonPath('data.role_label', 'Nhân viên kho');
    }

    public function test_dang_xuat_chi_thu_hoi_dung_token_dang_dung(): void
    {
        $user = $this->taoUser();
        $hienTai = $user->createToken('thiet-bi-1')->plainTextToken;
        $user->createToken('thiet-bi-2');

        $this->withToken($hienTai)->postJson('/api/auth/logout')->assertOk();

        // BR01.02-1: thiết bị khác vẫn đăng nhập
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'thiet-bi-1']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'thiet-bi-2']);

        // Token đã thu hồi không dùng lại được
        $this->quenXacThucApi();
        $this->withToken($hienTai)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_doi_mat_khau_thu_hoi_cac_token_khac_va_bo_co_buoc_doi(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => 'matkhau123']);
        $hienTai = $user->createToken('hien-tai')->plainTextToken;
        $user->createToken('thiet-bi-khac');

        // Route auth/password vẫn dùng được khi must_change_password = true (UC01.01 — A1)
        $this->withToken($hienTai)->putJson('/api/auth/password', [
            'current_password' => 'matkhau123',
            'password' => 'matkhau456',
            'password_confirmation' => 'matkhau456',
        ])->assertOk();

        $this->assertFalse($user->refresh()->must_change_password);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'hien-tai']);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'thiet-bi-khac']); // BR01.03-3
    }

    public function test_doi_mat_khau_sai_mat_khau_hien_tai_tra_422(): void
    {
        $this->apiNhanVien();

        $this->putJson('/api/auth/password', [
            'current_password' => 'sai-roi',
            'password' => 'matkhau456',
            'password_confirmation' => 'matkhau456',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'Mật khẩu hiện tại không đúng');
    }

    public function test_doi_mat_khau_yeu_hoac_khong_khop_xac_nhan_tra_422(): void
    {
        $this->apiNhanVien();

        $this->putJson('/api/auth/password', [
            'current_password' => 'password',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->putJson('/api/auth/password', [
            'current_password' => 'password',
            'password' => 'matkhau456',
            'password_confirmation' => 'khac-hoan-toan1',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_phai_doi_mat_khau_thi_cac_api_khac_tra_403(): void
    {
        Sanctum::actingAs(User::factory()->mustChangePassword()->create());

        $this->getJson('/api/categories')->assertStatus(403)->assertExactJson(['message' => 'Password change required']);
        $this->getJson('/api/dashboard/summary')->assertStatus(403);

        // vẫn xem được thông tin của chính mình
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.must_change_password', true);
    }

    public function test_tai_khoan_bi_khoa_giua_chung_bi_chan_403(): void
    {
        Sanctum::actingAs(User::factory()->locked()->create());

        $this->getJson('/api/categories')->assertStatus(403)->assertExactJson(['message' => 'Account is locked']);
    }

    public function test_xem_va_cap_nhat_ho_so_chi_doi_ten_va_so_dien_thoai(): void
    {
        $user = $this->apiNhanVien(['email' => 'nv@example.com']);

        $this->getJson('/api/profile')->assertOk()->assertJsonPath('data.email', 'nv@example.com');

        // BR01.04-1, BR01.04-2: email và vai trò không bao giờ được sửa ở đây
        $this->putJson('/api/profile', [
            'name' => 'Tên Mới',
            'phone' => '0912345678',
            'email' => 'hack@example.com',
            'role' => 'admin',
        ])->assertOk()->assertJsonPath('data.name', 'Tên Mới');

        $user->refresh();
        $this->assertSame('nv@example.com', $user->email);
        $this->assertSame(Role::Staff, $user->role);
        $this->assertSame('0912345678', $user->phone);
    }

    public function test_cap_nhat_ho_so_thieu_ten_tra_422(): void
    {
        $this->apiNhanVien();

        $this->putJson('/api/profile', ['name' => '', 'phone' => '0912345678'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Họ tên không được để trống.');
    }
}
