<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * UC02.04 – UC02.07 và UC04.02 — Sản phẩm, bảng API nhóm 3 (#21 – #28).
 */
class ApiProductTest extends TestCase
{
    use RefreshDatabase;

    /** Dữ liệu hợp lệ để tạo / sửa sản phẩm; $ghiDe thay đổi từng trường cần thử. */
    private function duLieuHopLe(Category $category, array $ghiDe = []): array
    {
        return $ghiDe + [
            'sku' => 'SP-001',
            'name' => 'Chuột không dây',
            'category_id' => $category->id,
            'unit' => 'cái',
            'price' => 250000,
            'min_stock_level' => 10,
        ];
    }

    public function test_chua_dang_nhap_tra_401(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    // ------------------------------------------------------------------ Danh sách (#21)

    public function test_danh_sach_kem_tong_ton_moi_kho_va_co_ton_thap(): void
    {
        $this->apiNhanVien();
        $thieu = Product::factory()->minStock(50)->create(['sku' => 'VP-1', 'name' => 'Giấy in']);
        $du = Product::factory()->minStock(5)->create(['sku' => 'VP-2', 'name' => 'Bút bi']);
        $khoA = Warehouse::factory()->create();
        $khoB = Warehouse::factory()->create();
        Inventory::factory()->quantity(20)->create(['product_id' => $thieu->id, 'warehouse_id' => $khoA->id]);
        Inventory::factory()->quantity(10)->create(['product_id' => $thieu->id, 'warehouse_id' => $khoB->id]);
        Inventory::factory()->quantity(8)->create(['product_id' => $du->id, 'warehouse_id' => $khoA->id]);

        // BR04.04-3: so ngưỡng với TỔNG tồn mọi kho. Giấy in: 20 + 10 = 30 < 50 -> thiếu 20; Bút bi: 8 >= 5 -> đủ
        $this->getJson('/api/products?sort=sku')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'sku', 'name', 'category_id', 'unit', 'price', 'avg_cost', 'min_stock_level', 'is_active', 'total_stock', 'is_low_stock', 'shortage']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.sku', 'VP-1')
            ->assertJsonPath('data.0.total_stock', 30)
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonPath('data.0.shortage', 20)
            ->assertJsonPath('data.1.total_stock', 8)
            ->assertJsonPath('data.1.is_low_stock', false)
            ->assertJsonPath('data.1.shortage', 0);
    }

    public function test_san_pham_chua_co_dong_ton_nao_coi_nhu_ton_0(): void
    {
        $this->apiNhanVien();
        Product::factory()->minStock(5)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.total_stock', 0)
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonPath('data.0.shortage', 5);
    }

    public function test_loc_chi_lay_san_pham_ton_thap(): void
    {
        $this->apiNhanVien();
        $thieu = Product::factory()->minStock(50)->create(['sku' => 'VP-1']);
        $du = Product::factory()->minStock(5)->create(['sku' => 'VP-2']);
        Inventory::factory()->quantity(30)->create(['product_id' => $thieu->id]);
        Inventory::factory()->quantity(8)->create(['product_id' => $du->id]);

        $this->getJson('/api/products?low_stock=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'VP-1');
    }

    public function test_tim_theo_sku_hoac_ten_va_loc_theo_danh_muc_trang_thai(): void
    {
        $this->apiNhanVien();
        $dienTu = Category::factory()->create();
        $giaDung = Category::factory()->create();
        Product::factory()->create(['sku' => 'DT-001', 'name' => 'Chuột không dây', 'category_id' => $dienTu->id]);
        Product::factory()->create(['sku' => 'GD-001', 'name' => 'Ấm đun siêu tốc', 'category_id' => $giaDung->id]);
        Product::factory()->inactive()->create(['sku' => 'GD-002', 'name' => 'Nồi cũ', 'category_id' => $giaDung->id]);

        // BR02.04-2: tìm theo SKU (không phân biệt hoa thường) hoặc tên
        $this->getJson('/api/products?keyword=gd-0')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/products?keyword=chuột')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sku', 'DT-001');

        // BR02.04-3: lọc theo danh mục và trạng thái kinh doanh
        $this->getJson("/api/products?category_id={$giaDung->id}")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/products?category_id={$giaDung->id}&is_active=0")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'GD-002');
    }

    public function test_tham_so_sap_xep_khong_hop_le_bi_bo_qua_chu_khong_gay_loi(): void
    {
        $this->apiNhanVien();
        Product::factory()->count(2)->create();

        $this->getJson('/api/products?sort=avg_cost;drop table products&direction=sideways&per_page=9999')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ------------------------------------------------------------------ Chi tiết (#24)

    public function test_chi_tiet_kem_ton_theo_tung_kho(): void
    {
        $this->apiNhanVien();
        $product = Product::factory()->create(['sku' => 'DT-001']);
        $kho = Warehouse::factory()->create(['code' => 'KHO-A']);
        Inventory::factory()->quantity(7)->create(['product_id' => $product->id, 'warehouse_id' => $kho->id]);

        // BR02.04-6
        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.sku', 'DT-001')
            ->assertJsonPath('data.total_stock', 7)
            ->assertJsonCount(1, 'data.inventories')
            ->assertJsonPath('data.inventories.0.quantity', 7)
            ->assertJsonPath('data.inventories.0.warehouse.code', 'KHO-A');
    }

    public function test_chi_tiet_khong_ton_tai_tra_404(): void
    {
        $this->apiNhanVien();

        $this->getJson('/api/products/99999')->assertStatus(404)->assertExactJson(['message' => 'Resource not found']);
    }

    // ------------------------------------------------------------------ Thêm (#23)

    public function test_admin_tao_san_pham_tra_201_gia_von_bang_0_va_ghi_nhat_ky(): void
    {
        $admin = $this->apiAdmin();
        $category = Category::factory()->create();

        // SKU được cắt khoảng trắng và viết hoa; avg_cost / is_active gửi lên bị bỏ qua (BR02.05-3)
        $this->postJson('/api/products', $this->duLieuHopLe($category, ['sku' => '  sp-001 ', 'avg_cost' => 999, 'is_active' => false]))
            ->assertCreated()
            ->assertJsonPath('data.sku', 'SP-001')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('products', ['sku' => 'SP-001', 'is_active' => 1]);
        $this->assertSame('0.0000', Product::query()->where('sku', 'SP-001')->firstOrFail()->avg_cost);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_created', 'user_id' => $admin->id]);
    }

    public function test_tao_san_pham_thieu_truong_bat_buoc_tra_422(): void
    {
        $this->apiAdmin();

        $this->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'name', 'category_id', 'unit', 'price', 'min_stock_level']);
    }

    public function test_tao_san_pham_sku_trung_hoac_sai_dinh_dang_tra_422(): void
    {
        $this->apiAdmin();
        $category = Category::factory()->create();
        Product::factory()->create(['sku' => 'SP-001']);

        // BR02.05-1: SKU duy nhất
        $this->postJson('/api/products', $this->duLieuHopLe($category))
            ->assertStatus(422)
            ->assertJsonPath('errors.sku.0', 'Mã SKU này đã tồn tại.');

        $this->postJson('/api/products', $this->duLieuHopLe($category, ['sku' => 'SP 002 !']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_tao_san_pham_danh_muc_khong_ton_tai_hoac_gia_am_tra_422(): void
    {
        $this->apiAdmin();
        $category = Category::factory()->create();

        $this->postJson('/api/products', $this->duLieuHopLe($category, ['category_id' => 99999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);

        $this->postJson('/api/products', $this->duLieuHopLe($category, ['price' => -1, 'min_stock_level' => -5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'min_stock_level']);
    }

    // ------------------------------------------------------------------ Sửa (#25)

    public function test_admin_sua_san_pham_va_duoc_giu_nguyen_sku_cua_chinh_no(): void
    {
        $admin = $this->apiAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['sku' => 'SP-001', 'name' => 'Cũ', 'category_id' => $category->id, 'min_stock_level' => 5]);

        $this->putJson("/api/products/{$product->id}", $this->duLieuHopLe($category, ['name' => 'Mới', 'min_stock_level' => 20]))
            ->assertOk()
            ->assertJsonPath('data.sku', 'SP-001')
            ->assertJsonPath('data.name', 'Mới')
            ->assertJsonPath('data.min_stock_level', 20);

        // BR02.06-5: ghi nhật ký giá trị cũ / mới
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_updated', 'user_id' => $admin->id]);
    }

    public function test_sua_sku_thanh_sku_cua_san_pham_khac_tra_422(): void
    {
        $this->apiAdmin();
        $category = Category::factory()->create();
        Product::factory()->create(['sku' => 'SP-001']);
        $khac = Product::factory()->create(['sku' => 'SP-002', 'category_id' => $category->id]);

        $this->putJson("/api/products/{$khac->id}", $this->duLieuHopLe($category, ['sku' => 'SP-001']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_doi_sku_khi_san_pham_da_co_giao_dich_tra_409(): void
    {
        $admin = $this->apiAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['sku' => 'SP-001', 'category_id' => $category->id]);
        $kho = Warehouse::factory()->create();

        // Một dòng thẻ kho đủ để coi là "đã phát sinh giao dịch" (BR02.06-3)
        app(InventoryService::class)->applyMovement($product, $kho, MovementType::Import, 10, 'imports', 1, '1000', '1000', $admin);

        $this->putJson("/api/products/{$product->id}", $this->duLieuHopLe($category, ['sku' => 'SP-002']))
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Sản phẩm đã phát sinh giao dịch, không được đổi SKU']);

        // Các trường khác vẫn sửa được khi giữ nguyên SKU
        $this->putJson("/api/products/{$product->id}", $this->duLieuHopLe($category, ['name' => 'Tên mới']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Tên mới');
    }

    // ------------------------------------------------------------------ Xóa (#26) và ngừng kinh doanh (#27)

    public function test_xoa_san_pham_chua_phat_sinh_gi_thanh_cong(): void
    {
        $admin = $this->apiAdmin();
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Xóa sản phẩm thành công');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_deleted', 'user_id' => $admin->id]);
    }

    public function test_xoa_san_pham_con_ton_hoac_da_co_giao_dich_tra_409(): void
    {
        $admin = $this->apiAdmin();
        $conTon = Product::factory()->create();
        Inventory::factory()->quantity(5)->create(['product_id' => $conTon->id]);

        // Nhập 3 rồi xuất 3: tồn về 0 nhưng thẻ kho đã có lịch sử
        $daGiaoDich = Product::factory()->create();
        $kho = Warehouse::factory()->create();
        $inventory = app(InventoryService::class);
        $inventory->applyMovement($daGiaoDich, $kho, MovementType::Import, 3, 'imports', 1, '1000', '1000', $admin);
        $inventory->applyMovement($daGiaoDich, $kho, MovementType::Export, -3, 'exports', 1, '1000', '1000', $admin);

        // UC02.07: hết tồn nhưng đã có lịch sử giao dịch vẫn không được xóa
        $this->deleteJson("/api/products/{$conTon->id}")->assertStatus(409);
        $this->deleteJson("/api/products/{$daGiaoDich->id}")->assertStatus(409);

        $this->assertDatabaseHas('products', ['id' => $conTon->id]);
        $this->assertDatabaseHas('products', ['id' => $daGiaoDich->id]);
    }

    public function test_ngung_kinh_doanh_roi_kinh_doanh_lai(): void
    {
        $admin = $this->apiAdmin();
        $product = Product::factory()->create(['is_active' => true]);

        $this->patchJson("/api/products/{$product->id}/toggle")->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_deactivated', 'user_id' => $admin->id]);

        $this->patchJson("/api/products/{$product->id}/toggle")->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_activated', 'user_id' => $admin->id]);
    }

    // ------------------------------------------------------------------ Kiểm tra SKU (#22)

    public function test_kiem_tra_sku_da_ton_tai_chua(): void
    {
        $this->apiAdmin();
        $product = Product::factory()->create(['sku' => 'SP-001']);

        $this->getJson('/api/products/check-sku?sku=sp-001')->assertOk()->assertJsonPath('data.exists', true);
        $this->getJson('/api/products/check-sku?sku=SP-999')->assertOk()->assertJsonPath('data.exists', false);

        // Khi sửa sản phẩm: loại trừ chính sản phẩm đang sửa
        $this->getJson("/api/products/check-sku?sku=SP-001&ignore_id={$product->id}")->assertOk()->assertJsonPath('data.exists', false);

        $this->getJson('/api/products/check-sku')->assertStatus(422)->assertJsonValidationErrors(['sku']);
    }

    // ------------------------------------------------------------------ Thẻ kho (#28, UC04.02)

    public function test_the_kho_co_ton_dau_ky_cac_bien_dong_va_ton_cuoi_ky(): void
    {
        $nguoi = $this->apiNhanVien();
        $product = Product::factory()->create();
        $kho = Warehouse::factory()->create();
        $inventory = app(InventoryService::class);

        $inventory->applyMovement($product, $kho, MovementType::Import, 10, 'imports', 1, '1000', '1000', $nguoi);
        $inventory->applyMovement($product, $kho, MovementType::Export, -4, 'exports', 1, '1000', '1000', $nguoi);

        $this->getJson("/api/products/{$product->id}/stock-card")
            ->assertOk()
            ->assertJsonPath('data.opening_balance', 0)
            ->assertJsonPath('data.closing_balance', 6)
            ->assertJsonCount(2, 'data.movements')
            ->assertJsonPath('data.movements.0.qty_change', 10)
            ->assertJsonPath('data.movements.0.balance_after', 10)
            ->assertJsonPath('data.movements.1.qty_change', -4)
            ->assertJsonPath('data.movements.1.balance_after', 6);

        // BR04.02-3: xem từ ngày sau các biến động -> tồn đầu kỳ = tổng các biến động trước đó, danh sách rỗng
        $this->getJson("/api/products/{$product->id}/stock-card?start_date=2026-11-03")
            ->assertOk()
            ->assertJsonPath('data.opening_balance', 6)
            ->assertJsonPath('data.closing_balance', 6)
            ->assertJsonCount(0, 'data.movements');

        // Khoảng ngày chứa cả hai biến động (02/11/2026 theo giờ cố định của TestCase)
        $this->getJson("/api/products/{$product->id}/stock-card?start_date=2026-11-02&end_date=2026-11-02")
            ->assertOk()
            ->assertJsonPath('data.opening_balance', 0)
            ->assertJsonCount(2, 'data.movements');
    }

    public function test_dong_the_kho_kem_so_phieu_cua_chung_tu_goc(): void
    {
        $nguoi = $this->apiNhanVien();
        $product = Product::factory()->create();
        $kho = Warehouse::factory()->create();
        $phieuNhap = Import::create([
            'code' => 'PN20261102-0001',
            'supplier_id' => Supplier::factory()->create()->id,
            'warehouse_id' => $kho->id,
            'import_date' => '2026-11-02',
            'status' => DocumentStatus::Confirmed,
            'total_amount' => 10000,
            'created_by' => $nguoi->id,
        ]);
        $inventory = app(InventoryService::class);

        $inventory->applyMovement($product, $kho, MovementType::Import, 10, 'imports', $phieuNhap->id, '1000', '1000', $nguoi);
        // Chứng từ gốc không còn tồn tại: số phiếu để trống chứ không báo lỗi
        $inventory->applyMovement($product, $kho, MovementType::Export, -1, 'exports', 999, '1000', '1000', $nguoi);

        // BR04.02-6 / BR04.02-7: mỗi dòng có số phiếu và (loại, id) chứng từ gốc để mở phiếu
        $this->getJson("/api/products/{$product->id}/stock-card")
            ->assertOk()
            ->assertJsonPath('data.movements.0.reference_type', 'imports')
            ->assertJsonPath('data.movements.0.reference_id', $phieuNhap->id)
            ->assertJsonPath('data.movements.0.reference_code', 'PN20261102-0001')
            ->assertJsonPath('data.movements.1.reference_code', null);
    }

    public function test_the_kho_ngay_ket_thuc_truoc_ngay_bat_dau_tra_422(): void
    {
        $this->apiNhanVien();
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}/stock-card?start_date=2026-11-05&end_date=2026-11-01")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_ban_ghi_the_kho_chi_ghi_them_khong_sua_khong_xoa(): void
    {
        $nguoi = $this->apiNhanVien();
        $product = Product::factory()->create();
        $kho = Warehouse::factory()->create();

        $movement = app(InventoryService::class)->applyMovement($product, $kho, MovementType::Import, 10, 'imports', 1, '1000', '1000', $nguoi);

        $this->expectException(LogicException::class);

        $movement->update(['qty_change' => 99]);
    }
}
