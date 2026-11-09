<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UC02.01 — Danh mục sản phẩm, bảng API nhóm 2 (#7 – #10): LÁT CẮT THAM CHIẾU đầy đủ
 * Route -> Form Request + Policy -> Service -> Resource -> { "data": ... }.
 */
class ApiCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_chua_dang_nhap_tra_401(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    public function test_danh_sach_co_khuon_data_meta_va_so_san_pham(): void
    {
        $this->apiAdmin();
        $dienThoai = Category::factory()->create(['name' => 'Điện thoại']);
        Category::factory()->create(['name' => 'Gia dụng']);
        Product::factory()->count(2)->create(['category_id' => $dienThoai->id]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'products_count', 'created_at', 'updated_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Điện thoại')   // mặc định sắp xếp A–Z
            ->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('data.1.products_count', 0);
    }

    public function test_nhan_vien_kho_xem_duoc_danh_sach_va_chi_tiet(): void
    {
        $this->apiNhanVien();
        $category = Category::factory()->create(['name' => 'Văn phòng phẩm']);

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/categories/{$category->id}")->assertOk()->assertJsonPath('data.name', 'Văn phòng phẩm');
    }

    public function test_tim_kiem_theo_tu_khoa(): void
    {
        $this->apiAdmin();
        Category::factory()->create(['name' => 'Điện thoại', 'description' => 'Smartphone']);
        Category::factory()->create(['name' => 'Gia dụng', 'description' => 'Đồ dùng nhà bếp']);

        $this->getJson('/api/categories?keyword=nhà bếp')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Gia dụng');
        $this->getJson('/api/categories?keyword=khong-co-gi')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_ky_tu_dac_biet_cua_like_duoc_escape(): void
    {
        $this->apiAdmin();
        Category::factory()->create(['name' => 'Giảm 100%']);
        Category::factory()->create(['name' => 'Giảm 1000']);

        // Nếu "%" không được escape thì "100%" khớp cả hai danh mục
        $this->getJson('/api/categories?keyword=100%25')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Giảm 100%');
    }

    public function test_tham_so_sap_xep_la_khong_hop_le_bi_bo_qua_chu_khong_gay_loi(): void
    {
        $this->apiAdmin();
        Category::factory()->count(2)->create();

        // Cột sắp xếp chỉ được lấy từ whitelist (CategoryFilter::SORTABLE) — chống SQL injection
        $this->getJson('/api/categories?sort=name;drop table users&direction=sideways&per_page=9999')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_sap_xep_va_phan_trang(): void
    {
        $this->apiAdmin();
        foreach (['B', 'A', 'C'] as $ten) {
            Category::factory()->create(['name' => "Nhóm {$ten}"]);
        }

        $this->getJson('/api/categories?sort=name&direction=desc&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Nhóm C')
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_chi_tiet_khong_ton_tai_tra_404_dang_json(): void
    {
        $this->apiAdmin();

        $this->getJson('/api/categories/99999')->assertStatus(404)->assertExactJson(['message' => 'Resource not found']);
    }

    public function test_admin_tao_danh_muc_tra_201_va_ghi_nhat_ky(): void
    {
        $admin = $this->apiAdmin();

        // BR02.01-1: tên được cắt khoảng trắng hai đầu
        $this->postJson('/api/categories', ['name' => '  Điện thoại  ', 'description' => 'Smartphone'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Điện thoại')
            ->assertJsonPath('data.description', 'Smartphone');

        $this->assertDatabaseHas('categories', ['name' => 'Điện thoại']);
        // BR02.01-5: ghi Activity Log
        $this->assertDatabaseHas('activity_logs', ['action' => 'category_created', 'user_id' => $admin->id]);
    }

    public function test_nhan_vien_kho_khong_duoc_tao_sua_xoa(): void
    {
        $this->apiNhanVien();
        $category = Category::factory()->create();

        $this->postJson('/api/categories', ['name' => 'Mới'])->assertForbidden()->assertExactJson(['message' => 'Forbidden']);
        $this->putJson("/api/categories/{$category->id}", ['name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => $category->name]);
    }

    public function test_ten_trong_tra_422(): void
    {
        $this->apiAdmin();

        $this->postJson('/api/categories', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Tên danh mục không được để trống.');

        $this->postJson('/api/categories', ['name' => '   '])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_ten_trung_tra_422(): void
    {
        $this->apiAdmin();
        Category::factory()->create(['name' => 'Điện thoại']);

        $this->postJson('/api/categories', ['name' => 'Điện thoại'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Tên danh mục này đã tồn tại.');
    }

    public function test_ten_qua_dai_tra_422(): void
    {
        $this->apiAdmin();

        $this->postJson('/api/categories', ['name' => str_repeat('a', 101)])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_admin_sua_danh_muc_va_duoc_giu_nguyen_ten_cua_chinh_no(): void
    {
        $admin = $this->apiAdmin();
        $category = Category::factory()->create(['name' => 'Điện thoại', 'description' => null]);

        // Gửi lại đúng tên cũ (chỉ đổi mô tả) không bị coi là trùng
        $this->putJson("/api/categories/{$category->id}", ['name' => 'Điện thoại', 'description' => 'Smartphone'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Smartphone');

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Điện thoại di động'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Điện thoại di động')
            ->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('activity_logs', ['action' => 'category_updated', 'user_id' => $admin->id]);
    }

    public function test_sua_thanh_ten_cua_danh_muc_khac_tra_422(): void
    {
        $this->apiAdmin();
        Category::factory()->create(['name' => 'Điện thoại']);
        $khac = Category::factory()->create(['name' => 'Gia dụng']);

        $this->putJson("/api/categories/{$khac->id}", ['name' => 'Điện thoại'])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_xoa_danh_muc_trong_thanh_cong(): void
    {
        $admin = $this->apiAdmin();
        $category = Category::factory()->create(['name' => 'Tạm']);

        $this->deleteJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Xóa danh mục thành công');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'category_deleted', 'user_id' => $admin->id]);
    }

    public function test_xoa_danh_muc_con_san_pham_tra_409(): void
    {
        $this->apiAdmin();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // BR02.01-3
        $this->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(409)
            ->assertExactJson(['message' => 'Danh mục còn sản phẩm, không thể xóa']);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
