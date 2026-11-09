<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC02.03 — Kho, bảng API nhóm 2 (#16 – #20) và GET /api/warehouses/{id}/inventories (sơ đồ lớp thiết kế).
 */
class ApiWarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_chua_dang_nhap_tra_401(): void
    {
        $this->getJson('/api/warehouses')->assertStatus(401);
    }

    public function test_danh_sach_kem_tong_ton_cua_tung_kho(): void
    {
        $this->apiNhanVien();
        $hn = Warehouse::factory()->create(['code' => 'KHO-HN', 'name' => 'Kho Hà Nội', 'address' => 'Số 1 Phố A']);
        Warehouse::factory()->create(['code' => 'KHO-HCM', 'name' => 'Kho TP.HCM', 'address' => 'Số 2 Phố B']);
        Inventory::factory()->quantity(5)->create(['warehouse_id' => $hn->id]);
        Inventory::factory()->quantity(7)->create(['warehouse_id' => $hn->id]);

        $this->getJson('/api/warehouses?sort=code')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'address', 'is_active', 'total_stock']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.code', 'KHO-HCM')   // sắp xếp A–Z theo mã
            ->assertJsonPath('data.0.total_stock', 0)
            ->assertJsonPath('data.1.code', 'KHO-HN')
            ->assertJsonPath('data.1.total_stock', 12);
    }

    public function test_tim_kiem_va_loc_theo_trang_thai(): void
    {
        $this->apiNhanVien();
        Warehouse::factory()->create(['code' => 'KHO-HN', 'name' => 'Kho Hà Nội', 'address' => 'Số 1 Phố A']);
        Warehouse::factory()->inactive()->create(['code' => 'KHO-CU', 'name' => 'Kho cũ', 'address' => 'Số 2 Phố B']);

        $this->getJson('/api/warehouses?keyword=hà nội')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'KHO-HN');
        $this->getJson('/api/warehouses?is_active=0')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'KHO-CU');
    }

    public function test_chi_tiet_kho_va_kho_khong_ton_tai(): void
    {
        $this->apiNhanVien();
        $kho = Warehouse::factory()->create(['code' => 'KHO-HN']);
        Inventory::factory()->quantity(3)->create(['warehouse_id' => $kho->id]);

        $this->getJson("/api/warehouses/{$kho->id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'KHO-HN')
            ->assertJsonPath('data.total_stock', 3);

        $this->getJson('/api/warehouses/99999')->assertStatus(404)->assertExactJson(['message' => 'Resource not found']);
    }

    public function test_ton_tung_san_pham_trong_kho(): void
    {
        $this->apiNhanVien();
        $kho = Warehouse::factory()->create();
        $a = Product::factory()->create(['sku' => 'SP-A']);
        $b = Product::factory()->create(['sku' => 'SP-B']);
        Inventory::factory()->quantity(4)->create(['warehouse_id' => $kho->id, 'product_id' => $a->id]);
        Inventory::factory()->quantity(9)->create(['warehouse_id' => $kho->id, 'product_id' => $b->id]);
        Inventory::factory()->quantity(1)->create();   // một kho khác, không được lẫn vào

        $this->getJson("/api/warehouses/{$kho->id}/inventories")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.product.sku', 'SP-A')
            ->assertJsonPath('data.0.quantity', 4)
            ->assertJsonPath('data.1.product.sku', 'SP-B')
            ->assertJsonPath('data.1.quantity', 9);
    }

    public function test_admin_tao_kho_tra_201_ma_viet_hoa_va_ghi_nhat_ky(): void
    {
        $admin = $this->apiAdmin();

        $this->postJson('/api/warehouses', ['code' => ' kho-hn ', 'name' => ' Kho Hà Nội ', 'address' => 'Số 55 Giải Phóng'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'KHO-HN')
            ->assertJsonPath('data.name', 'Kho Hà Nội')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('warehouses', ['code' => 'KHO-HN', 'is_active' => 1]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'warehouse_created', 'user_id' => $admin->id]);
    }

    public function test_tao_kho_thieu_truong_trung_ma_hoac_ma_sai_dinh_dang_tra_422(): void
    {
        $this->apiAdmin();
        Warehouse::factory()->create(['code' => 'KHO-HN']);

        $this->postJson('/api/warehouses', [])->assertStatus(422)->assertJsonValidationErrors(['code', 'name']);

        // BR02.03-1: mã kho duy nhất (so sánh sau khi viết hoa)
        $this->postJson('/api/warehouses', ['code' => 'kho-hn', 'name' => 'Trùng mã'])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'Mã kho này đã tồn tại.');

        $this->postJson('/api/warehouses', ['code' => 'KHO HN', 'name' => 'Mã có khoảng trắng'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_sua_kho_va_duoc_giu_nguyen_ma_cua_chinh_no(): void
    {
        $admin = $this->apiAdmin();
        $kho = Warehouse::factory()->create(['code' => 'KHO-HN', 'name' => 'Cũ']);
        Warehouse::factory()->create(['code' => 'KHO-HCM']);

        $this->putJson("/api/warehouses/{$kho->id}", ['code' => 'KHO-HN', 'name' => 'Kho Hà Nội', 'address' => 'Số 55 Giải Phóng'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Kho Hà Nội')
            ->assertJsonPath('data.address', 'Số 55 Giải Phóng');

        $this->assertDatabaseHas('activity_logs', ['action' => 'warehouse_updated', 'user_id' => $admin->id]);

        // Đổi sang mã của kho khác -> trùng
        $this->putJson("/api/warehouses/{$kho->id}", ['code' => 'KHO-HCM', 'name' => 'Kho Hà Nội'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_ngung_hoat_dong_chi_khi_kho_het_hang_va_kich_hoat_lai_duoc(): void
    {
        $admin = $this->apiAdmin();
        $kho = Warehouse::factory()->create(['is_active' => true]);

        $this->patchJson("/api/warehouses/{$kho->id}/toggle")->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('activity_logs', ['action' => 'warehouse_deactivated', 'user_id' => $admin->id]);

        $this->patchJson("/api/warehouses/{$kho->id}/toggle")->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('activity_logs', ['action' => 'warehouse_activated', 'user_id' => $admin->id]);
    }

    public function test_kho_con_hang_ton_khong_the_ngung_hoat_dong_tra_409(): void
    {
        $this->apiAdmin();
        $kho = Warehouse::factory()->create(['is_active' => true]);
        Inventory::factory()->quantity(3)->create(['warehouse_id' => $kho->id]);

        // BR02.03-3
        $this->patchJson("/api/warehouses/{$kho->id}/toggle")
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Kho còn hàng tồn, không thể ngừng hoạt động']);

        $this->assertDatabaseHas('warehouses', ['id' => $kho->id, 'is_active' => 1]);
    }

    public function test_xoa_kho_chua_phat_sinh_gi_thanh_cong(): void
    {
        $admin = $this->apiAdmin();
        $kho = Warehouse::factory()->create();

        $this->deleteJson("/api/warehouses/{$kho->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Xóa kho thành công');

        $this->assertDatabaseMissing('warehouses', ['id' => $kho->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'warehouse_deleted', 'user_id' => $admin->id]);
    }

    public function test_xoa_kho_da_tung_co_dong_ton_tra_409(): void
    {
        $this->apiAdmin();
        $kho = Warehouse::factory()->create();
        Inventory::factory()->quantity(0)->create(['warehouse_id' => $kho->id]);

        // BR02.03-4: đã từng phát sinh tồn kho (kể cả hiện bằng 0) thì chỉ được ngừng hoạt động
        $this->deleteJson("/api/warehouses/{$kho->id}")->assertStatus(409);

        $this->assertDatabaseHas('warehouses', ['id' => $kho->id]);
    }

    public function test_nhan_vien_kho_khong_duoc_tao_sua_xoa_ngung_kho(): void
    {
        $this->apiNhanVien();
        $kho = Warehouse::factory()->create();

        $this->postJson('/api/warehouses', ['code' => 'KHO-X', 'name' => 'Kho X'])->assertForbidden()->assertExactJson(['message' => 'Forbidden']);
        $this->putJson("/api/warehouses/{$kho->id}", ['code' => $kho->code, 'name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/warehouses/{$kho->id}")->assertForbidden();
        $this->patchJson("/api/warehouses/{$kho->id}/toggle")->assertForbidden();

        $this->assertDatabaseHas('warehouses', ['id' => $kho->id, 'name' => $kho->name, 'is_active' => 1]);
    }
}
