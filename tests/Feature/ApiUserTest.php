<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * UC06.01 — Quản lý tài khoản người dùng (chỉ Admin), bảng API nhóm 7 (#56 – #60).
 */
class ApiUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_danh_sach_khong_lo_mat_khau_va_loc_theo_vai_tro_trang_thai_tu_khoa(): void
    {
        $this->apiAdmin();
        User::factory()->staff()->create(['name' => 'An', 'email' => 'an@inventory.test']);
        User::factory()->staff()->locked()->create(['name' => 'Bình', 'email' => 'binh@inventory.test']);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role', 'role_label', 'status', 'phone', 'must_change_password', 'last_login_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token');

        $this->getJson('/api/users?role=admin')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/users?role=staff')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/users?status=locked')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'binh@inventory.test');
        $this->getJson('/api/users?keyword=an@inv')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'An');
    }

    public function test_xem_chi_tiet_mot_tai_khoan(): void
    {
        $this->apiAdmin();
        $nv = User::factory()->staff()->create(['email' => 'an@inventory.test']);

        $this->getJson("/api/users/{$nv->id}")
            ->assertOk()
            ->assertJsonPath('data.email', 'an@inventory.test')
            ->assertJsonPath('data.role_label', 'Nhân viên kho');

        $this->getJson('/api/users/99999')->assertStatus(404);
    }

    public function test_admin_tao_tai_khoan_buoc_doi_mat_khau_va_khong_ghi_mat_khau_vao_nhat_ky(): void
    {
        $admin = $this->apiAdmin();

        $this->postJson('/api/users', [
            'name' => '  Nguyễn Văn A ',
            'email' => ' NhanVien@Inventory.test ',
            'role' => 'staff',
            'phone' => '0901234567',
            'password' => 'Tam12345',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nguyễn Văn A')
            ->assertJsonPath('data.email', 'nhanvien@inventory.test')
            ->assertJsonPath('data.role', 'staff')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.must_change_password', true)   // BR06.01-3
            ->assertJsonMissingPath('data.password');

        $user = User::query()->where('email', 'nhanvien@inventory.test')->firstOrFail();
        $this->assertTrue(Hash::check('Tam12345', $user->password));   // BR01.01-1: băm bcrypt, không lưu mật khẩu rõ

        $this->assertDatabaseHas('activity_logs', ['action' => 'user_created', 'user_id' => $admin->id]);
        $log = ActivityLog::query()->where('action', 'user_created')->firstOrFail();
        $this->assertArrayNotHasKey('password', $log->properties['new']);   // BR06.01-10
    }

    public function test_tao_tai_khoan_trung_email_mat_khau_yeu_hoac_vai_tro_la_tra_422(): void
    {
        $this->apiAdmin();
        User::factory()->create(['email' => 'an@inventory.test']);

        $hopLe = ['name' => 'Bình', 'email' => 'binh@inventory.test', 'role' => 'staff', 'password' => 'Tam12345'];

        $this->postJson('/api/users', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'role', 'password']);

        // BR06.01-4: email duy nhất (so sánh sau khi chuẩn hóa chữ thường)
        $this->postJson('/api/users', ['email' => 'AN@inventory.test'] + $hopLe)->assertStatus(422)->assertJsonValidationErrors(['email']);

        // BR01.03-2: mật khẩu tối thiểu 8 ký tự, có chữ và số
        $this->postJson('/api/users', ['password' => 'abc'] + $hopLe)->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->postJson('/api/users', ['password' => 'chiCoChuCai'] + $hopLe)->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('/api/users', ['role' => 'boss'] + $hopLe)->assertStatus(422)->assertJsonValidationErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'binh@inventory.test']);
    }

    public function test_admin_sua_ten_vai_tro_so_dien_thoai(): void
    {
        $admin = $this->apiAdmin();
        $nv = User::factory()->staff()->create(['name' => 'Cũ', 'phone' => '0900000000']);

        $this->putJson("/api/users/{$nv->id}", ['name' => 'Mới', 'role' => 'admin', 'phone' => '0911111111'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Mới')
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.phone', '0911111111');

        $this->assertDatabaseHas('activity_logs', ['action' => 'user_updated', 'user_id' => $admin->id]);
    }

    public function test_khong_ha_quyen_admin_duy_nhat_nhung_ha_duoc_khi_con_admin_khac(): void
    {
        $admin = $this->apiAdmin();

        // BR06.01-6: chỉ còn một Admin đang hoạt động
        $this->putJson("/api/users/{$admin->id}", ['name' => $admin->name, 'role' => 'staff'])
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Không thể hạ quyền Admin duy nhất còn lại']);

        User::factory()->admin()->create();

        $this->putJson("/api/users/{$admin->id}", ['name' => $admin->name, 'role' => 'staff'])
            ->assertOk()
            ->assertJsonPath('data.role', 'staff');
    }

    public function test_khoa_roi_mo_khoa_tai_khoan_va_thu_hoi_token_ngay_khi_khoa(): void
    {
        $admin = $this->apiAdmin();
        $nv = User::factory()->staff()->create();
        $nv->createToken('thiet-bi-cu');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->patchJson("/api/users/{$nv->id}/toggle")->assertOk()->assertJsonPath('data.status', 'locked');

        $this->assertDatabaseHas('users', ['id' => $nv->id, 'status' => 'locked']);
        $this->assertDatabaseCount('personal_access_tokens', 0);   // BR06.01-8
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_locked', 'user_id' => $admin->id]);

        $this->patchJson("/api/users/{$nv->id}/toggle")->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_unlocked', 'user_id' => $admin->id]);
    }

    public function test_admin_khong_tu_khoa_chinh_minh(): void
    {
        $admin = $this->apiAdmin();

        // BR06.01-7
        $this->patchJson("/api/users/{$admin->id}/toggle")
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Bạn không thể tự khóa tài khoản đang đăng nhập']);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_dat_lai_mat_khau_cap_mat_khau_tam_mot_lan_va_buoc_doi(): void
    {
        $admin = $this->apiAdmin();
        $nv = User::factory()->staff()->create(['must_change_password' => false]);
        $nv->createToken('thiet-bi-cu');

        $response = $this->postJson("/api/users/{$nv->id}/reset-password")
            ->assertOk()
            ->assertJsonStructure(['data' => ['user' => ['id', 'email'], 'temporary_password']])
            ->assertJsonPath('data.user.must_change_password', true);

        // BR06.01-9: mật khẩu tạm đủ độ dài cấu hình (10), chỉ hiện đúng một lần và là mật khẩu mới của tài khoản
        $matKhauTam = $response->json('data.temporary_password');
        $this->assertSame(10, strlen($matKhauTam));
        $this->assertTrue(Hash::check($matKhauTam, $nv->fresh()->password));
        $this->assertTrue($nv->fresh()->must_change_password);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_password_reset', 'user_id' => $admin->id]);
        $this->assertDatabaseMissing('activity_logs', ['description' => $matKhauTam]);
    }
}
