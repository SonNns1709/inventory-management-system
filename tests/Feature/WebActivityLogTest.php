<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC06.02 — giao diện web Nhật ký hoạt động (Livewire): chỉ Admin, chỉ đọc, lọc theo ngày / người dùng / hành động.
 */
class WebActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap(): void
    {
        $this->get('/activity-logs')->assertRedirect('/login');
    }

    public function test_nhan_vien_kho_khong_xem_duoc_nhat_ky(): void
    {
        $this->dangNhapNhanVien();

        $this->get('/activity-logs')->assertForbidden();
    }

    public function test_admin_xem_duoc_nhat_ky_va_dong_khong_co_nguoi_dung(): void
    {
        $admin = $this->dangNhapAdmin(['name' => 'Nguyen Thi Lan']);
        $logs = app(ActivityLogService::class);
        $logs->record('product_updated', 'products', 'Sua san pham SP-0033', ['old' => ['name' => 'A'], 'new' => ['name' => 'B']], $admin, '192.168.1.10');
        // Đăng nhập sai email lạ: chưa có ai đăng nhập nên user_id rỗng (record() sẽ lấy Auth::user() nếu truyền null, nên tạo thẳng bản ghi)
        ActivityLog::create(['action' => 'login_failed', 'ip_address' => '203.0.113.45']);

        $this->get('/activity-logs')->assertOk()->assertSee('Quản trị / Nhật ký hoạt động');

        Livewire::test(ActivityLogIndex::class)
            ->assertSee('product_updated')
            ->assertSee('Nguyen Thi Lan')
            ->assertSee('192.168.1.10')
            ->assertSee('login_failed')
            ->assertSee('(không xác định)')
            ->assertSee('203.0.113.45');
    }

    public function test_loc_theo_hanh_dong_va_nguoi_dung(): void
    {
        $admin = $this->dangNhapAdmin(['name' => 'Admin Mot']);
        $nhanVien = $this->taoUser(Role::Staff, ['name' => 'Nhan Vien Hai']);
        $logs = app(ActivityLogService::class);
        $logs->record('import_confirmed', 'imports', 'Xac nhan phieu nhap', null, $nhanVien);
        $logs->record('user_locked', 'users', 'Khoa tai khoan', null, $admin);

        Livewire::test(ActivityLogIndex::class)
            ->set('action', 'user_locked')
            ->assertSee('Khoa tai khoan')
            ->assertDontSee('Xac nhan phieu nhap')
            ->set('action', '')
            ->set('userId', (string) $nhanVien->id)
            ->assertSee('Xac nhan phieu nhap')
            ->assertDontSee('Khoa tai khoan')
            ->call('clearFilters')
            ->assertSee('Khoa tai khoan')
            ->assertSee('Xac nhan phieu nhap');
    }

    public function test_loc_theo_khoang_ngay(): void
    {
        $this->dangNhapAdmin();
        // Sổ chỉ ghi thêm (model cấm update) nên chèn thẳng bản ghi cũ bằng query builder
        DB::table('activity_logs')->insert(['action' => 'login_success', 'description' => 'Ban ghi cu', 'created_at' => '2026-09-20 17:05:22']);
        ActivityLog::create(['action' => 'login_success', 'description' => 'Ban ghi moi']); // 02/11/2026 (giờ cố định của TestCase)

        Livewire::test(ActivityLogIndex::class)
            ->set('from', '2026-09-20')
            ->set('to', '2026-09-21')
            ->assertSee('Ban ghi cu')
            ->assertDontSee('Ban ghi moi');
    }

    public function test_ngay_sai_tren_url_khong_gay_loi(): void
    {
        $this->dangNhapAdmin();

        $this->get('/activity-logs?from=abc&to=2026-02-31&user=x')->assertOk();
    }

    public function test_nhat_ky_chi_doc_khong_co_nut_sua_xoa(): void
    {
        $this->dangNhapAdmin();
        app(ActivityLogService::class)->record('login_success', null, 'Dang nhap');

        $html = Livewire::test(ActivityLogIndex::class)->html();

        $this->assertStringNotContainsString('wire:click="delete', $html);
        $this->assertStringNotContainsString('wire:click="edit', $html);
    }
}
