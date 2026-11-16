<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Livewire\Users\UserIndex;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC06.01 — giao diện web Quản lý tài khoản (Livewire): chỉ Admin, tạo / sửa / khóa / đặt lại mật khẩu, các quy tắc BR06.01-*.
 */
class WebUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_nhan_vien_kho_khong_vao_duoc_trang_nguoi_dung(): void
    {
        $this->dangNhapNhanVien();

        $this->get('/users')->assertForbidden();
    }

    public function test_admin_xem_danh_sach_va_loc_theo_vai_tro_trang_thai(): void
    {
        $this->dangNhapAdmin(['name' => 'Admin Mot', 'email' => 'admin1@example.com']);
        $this->taoUser(Role::Staff, ['name' => 'Nhan Vien Hai', 'email' => 'nv2@example.com']);
        $this->taoUser(Role::Staff, ['name' => 'Nhan Vien Khoa', 'email' => 'nv3@example.com', 'status' => UserStatus::Locked]);

        $this->get('/users')->assertOk()->assertSee('Quản trị / Người dùng');

        Livewire::test(UserIndex::class)
            ->assertSee('Admin Mot')
            ->assertSee('Nhan Vien Hai')
            ->assertSee('Chưa đăng nhập')
            ->set('roleFilter', 'admin')
            ->assertSee('Admin Mot')
            ->assertDontSee('Nhan Vien Hai')
            ->set('roleFilter', '')
            ->set('statusFilter', 'locked')
            ->assertSee('Nhan Vien Khoa')
            ->assertDontSee('Nhan Vien Hai')
            ->call('clearFilters')
            ->set('keyword', 'nv2@')
            ->assertSee('Nhan Vien Hai')
            ->assertDontSee('Nhan Vien Khoa');
    }

    public function test_tao_tai_khoan_buoc_doi_mat_khau_va_ghi_nhat_ky(): void
    {
        $this->dangNhapAdmin();

        Livewire::test(UserIndex::class)
            ->call('create')
            ->set('name', 'Le Minh Tuan')
            ->set('email', ' TUAN.LE@Example.com ')
            ->set('formRole', 'staff')
            ->set('phone', '0912345678')
            ->set('password', 'Tamthoi123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSee('Đã tạo tài khoản');

        $user = User::query()->where('email', 'tuan.le@example.com')->firstOrFail();
        $this->assertSame(Role::Staff, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->isActive());
        $this->assertNotSame('Tamthoi123', $user->password);

        $log = ActivityLog::query()->where('action', 'user_created')->firstOrFail();
        $this->assertStringNotContainsString('Tamthoi123', json_encode($log->properties).$log->description);
    }

    public function test_tao_tai_khoan_bao_loi_email_trung_va_mat_khau_yeu(): void
    {
        $this->dangNhapAdmin();
        $this->taoUser(Role::Staff, ['email' => 'trung@example.com']);

        Livewire::test(UserIndex::class)
            ->call('create')
            ->set('name', 'Nguoi Moi')
            ->set('email', 'trung@example.com')
            ->set('password', 'abc')
            ->call('save')
            ->assertHasErrors(['email', 'password'])
            ->assertSet('showForm', true);
    }

    public function test_sua_tai_khoan_chi_doi_ten_vai_tro_sdt(): void
    {
        $this->dangNhapAdmin();
        $nv = $this->taoUser(Role::Staff, ['name' => 'Ten Cu', 'email' => 'giu@example.com']);

        Livewire::test(UserIndex::class)
            ->call('edit', $nv->id)
            ->assertSet('email', 'giu@example.com')
            ->set('name', 'Ten Moi')
            ->set('email', 'doi@example.com') // email không có trong luật sửa nên bị bỏ qua
            ->set('phone', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Đã cập nhật tài khoản');

        $nv->refresh();
        $this->assertSame('Ten Moi', $nv->name);
        $this->assertSame('giu@example.com', $nv->email);
        $this->assertNull($nv->phone);
    }

    public function test_khong_ha_quyen_admin_duy_nhat(): void
    {
        $admin = $this->dangNhapAdmin();

        Livewire::test(UserIndex::class)
            ->call('edit', $admin->id)
            ->set('formRole', 'staff')
            ->call('save')
            ->assertSee('Không thể hạ quyền Admin duy nhất còn lại');

        $this->assertSame(Role::Admin, $admin->refresh()->role);
    }

    public function test_khoa_va_mo_khoa_tai_khoan_khac(): void
    {
        $this->dangNhapAdmin();
        $nv = $this->taoUser(Role::Staff);

        Livewire::test(UserIndex::class)
            ->call('confirmAction', $nv->id, 'lock')
            ->assertSet('confirmingId', $nv->id)
            ->call('runConfirmed')
            ->assertSet('confirmingId', null)
            ->assertSee('Đã khóa tài khoản');

        $this->assertFalse($nv->refresh()->isActive());

        Livewire::test(UserIndex::class)->call('unlock', $nv->id)->assertSee('Đã mở khóa tài khoản');

        $this->assertTrue($nv->refresh()->isActive());
    }

    public function test_khong_tu_khoa_chinh_minh(): void
    {
        $admin = $this->dangNhapAdmin();
        $this->taoUser(Role::Admin); // có Admin thứ hai nên chỉ còn quy tắc "không tự khóa mình" (BR06.01-7) được thử

        Livewire::test(UserIndex::class)
            ->call('confirmAction', $admin->id, 'lock')
            ->call('runConfirmed')
            ->assertSee('Bạn không thể tự khóa tài khoản đang đăng nhập');

        $this->assertTrue($admin->refresh()->isActive());
    }

    public function test_dat_lai_mat_khau_hien_mot_lan_va_buoc_doi_mat_khau(): void
    {
        $this->dangNhapAdmin();
        $nv = $this->taoUser(Role::Staff, ['email' => 'datlai@example.com']);

        $component = Livewire::test(UserIndex::class)
            ->call('confirmAction', $nv->id, 'reset')
            ->call('runConfirmed')
            ->assertSee('Mật khẩu tạm')
            ->assertSet('revealedEmail', 'datlai@example.com')
            ->assertNotSet('revealedPassword', '');

        $this->assertTrue($nv->refresh()->must_change_password);
        $this->assertNotEmpty(ActivityLog::query()->where('action', 'user_password_reset')->first());

        $component->call('closeReveal')->assertSet('revealedPassword', '')->assertSet('revealedEmail', '');
    }

    public function test_hanh_dong_khong_hop_le_bi_tu_choi(): void
    {
        $this->dangNhapAdmin();
        $nv = $this->taoUser(Role::Staff);

        Livewire::test(UserIndex::class)->call('confirmAction', $nv->id, 'delete')->assertStatus(422);
    }
}
