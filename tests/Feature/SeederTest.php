<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Import;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Dữ liệu mẫu (database/seeders): đủ để demo ngay sau `php artisan migrate --seed`, và chạy lại nhiều lần không nhân đôi dữ liệu.
 *
 * Con số trong test này khớp InventorySeeder: 2 phiếu nhập tồn đầu kỳ (mỗi kho một phiếu), 20 dòng tồn,
 * tổng giá trị tồn 39.025.000 ₫ và đúng 4 sản phẩm dưới ngưỡng cảnh báo (BR04.04).
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_nap_du_lieu_mau_dung_so_luong(): void
    {
        $this->seed();

        $this->assertSoLuongDuLieuMau();
    }

    public function test_chay_lai_seeder_khong_nhan_doi_du_lieu(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSoLuongDuLieuMau();
    }

    public function test_tai_khoan_mau_dung_vai_tro_va_trang_thai(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', 'admin@inventory.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isActive());
        $this->assertTrue(Hash::check('Admin@123', $admin->password));
        $this->assertFalse($admin->must_change_password);

        $nhanVien = User::query()->where('email', 'staff@inventory.test')->firstOrFail();
        $this->assertTrue($nhanVien->isStaff());
        $this->assertTrue(Hash::check('Staff@123', $nhanVien->password));

        // UC01.01 Exception Flow E2: tài khoản bị khóa
        $biKhoa = User::query()->where('email', 'locked@inventory.test')->firstOrFail();
        $this->assertFalse($biKhoa->isActive());

        // UC01.01 Alternative Flow A1: buộc đổi mật khẩu ở lần đăng nhập đầu
        $moi = User::query()->where('email', 'newbie@inventory.test')->firstOrFail();
        $this->assertTrue($moi->must_change_password);
    }

    public function test_ton_dau_ky_la_hai_phieu_nhap_da_xac_nhan_va_moi_dong_ton_co_the_kho(): void
    {
        $this->seed();

        $phieu = Import::query()->orderBy('id')->get();

        $this->assertCount(2, $phieu);
        $this->assertSame(['PN20261102-0001', 'PN20261102-0002'], $phieu->pluck('code')->all());
        $this->assertTrue($phieu->every(fn (Import $p) => $p->status === DocumentStatus::Confirmed));

        // Mỗi dòng tồn đều có đúng một dòng thẻ kho (BR04.02-4)
        $this->assertDatabaseCount('inventories', 20);
        $this->assertDatabaseCount('stock_movements', 20);
    }

    public function test_du_lieu_mau_cho_ra_4_san_pham_ton_thap_sap_theo_muc_thieu(): void
    {
        $this->seed();

        $skus = app(InventoryService::class)->getLowStock()->pluck('sku')->all();

        // Thiếu 20, 18, 3, 3 (hai sản phẩm thiếu bằng nhau xếp theo id); DT-0099 đã ngừng kinh doanh nên không bị tính
        $this->assertSame(['VP-0001', 'DT-0003', 'DT-0004', 'TP-0002'], $skus);
    }

    public function test_dashboard_api_sau_khi_nap_du_lieu_mau(): void
    {
        $this->seed();
        $this->apiNhanVien();

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.total_products', 12)
            ->assertJsonPath('data.low_stock', 4)
            ->assertJsonPath('data.todays_import', 2)
            ->assertJsonPath('data.todays_export', 0)
            ->assertJsonPath('data.inventory_value', '39025000.00')
            ->assertJsonCount(4, 'data.low_stock_items')
            ->assertJsonPath('data.low_stock_items.0.sku', 'VP-0001')
            ->assertJsonPath('data.low_stock_items.0.shortage', 20);
    }

    public function test_dashboard_giao_dien_hien_thi_5_chi_so_sau_khi_nap_du_lieu_mau(): void
    {
        $this->seed();
        $this->dangNhapNhanVien();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Total Products')
            ->assertSee('Low Stock')
            ->assertSee('Inventory Value')
            ->assertSee('39.025.000 ₫');
    }

    private function assertSoLuongDuLieuMau(): void
    {
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('categories', 5);
        $this->assertDatabaseCount('suppliers', 5);
        $this->assertDatabaseCount('warehouses', 3);
        $this->assertDatabaseCount('products', 13);
        $this->assertDatabaseCount('imports', 2);
        $this->assertDatabaseCount('import_items', 20);
        $this->assertDatabaseCount('inventories', 20);
        $this->assertDatabaseCount('stock_movements', 20);
        $this->assertDatabaseCount('document_sequences', 1);
    }
}
