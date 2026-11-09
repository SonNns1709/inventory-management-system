<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phân quyền theo vai trò (báo cáo Chương 1 Bảng 3, Chương 2 Bảng 9) áp lên REST API:
 *   - chưa đăng nhập          -> 401 { "message": "Unauthenticated" }
 *   - Nhân viên kho đụng tới chức năng của Admin -> 403 { "message": "Forbidden" }
 *   - Admin và Nhân viên kho cùng đọc được danh mục, tồn kho, Dashboard...
 *
 * Mỗi route đặt middleware `role:admin` ngay ở routes/api.php; Policy / Form Request là lớp bảo vệ thứ hai.
 */
class ApiPermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> các endpoint cần token nhưng không phụ thuộc bản ghi nào */
    public static function endpointCanDangNhap(): array
    {
        return [
            'dashboard' => ['GET', '/api/dashboard/summary'],
            'danh mục' => ['GET', '/api/categories'],
            'nhà cung cấp' => ['GET', '/api/suppliers'],
            'kho' => ['GET', '/api/warehouses'],
            'sản phẩm' => ['GET', '/api/products'],
            'tồn kho' => ['GET', '/api/inventory'],
            'tồn thấp' => ['GET', '/api/inventory/low-stock'],
            'giá trị tồn' => ['GET', '/api/inventory/total-value'],
            'phiếu nhập' => ['GET', '/api/imports'],
            'phiếu xuất' => ['GET', '/api/exports'],
            'phiếu kiểm kê' => ['GET', '/api/adjustments'],
            'hồ sơ' => ['GET', '/api/profile'],
            'người dùng' => ['GET', '/api/users'],
            'nhật ký' => ['GET', '/api/activity-logs'],
            'báo cáo' => ['GET', '/api/reports/stock-summary'],
        ];
    }

    /** @return array<string, array{0: string, 1: string}> các endpoint dành riêng cho Admin, không cần bản ghi có sẵn */
    public static function endpointChiAdmin(): array
    {
        return [
            'tạo danh mục' => ['POST', '/api/categories'],
            'tạo nhà cung cấp' => ['POST', '/api/suppliers'],
            'tạo kho' => ['POST', '/api/warehouses'],
            'tạo sản phẩm' => ['POST', '/api/products'],
            'kiểm tra SKU' => ['GET', '/api/products/check-sku?sku=SP-001'],
            'danh sách người dùng' => ['GET', '/api/users'],
            'tạo người dùng' => ['POST', '/api/users'],
            'nhật ký hoạt động' => ['GET', '/api/activity-logs'],
            'báo cáo nhập - xuất - tồn' => ['GET', '/api/reports/stock-summary'],
            'xuất báo cáo nhập - xuất - tồn' => ['GET', '/api/reports/stock-summary/export'],
            'báo cáo kiểm kê' => ['GET', '/api/reports/adjustments'],
            'xuất báo cáo kiểm kê' => ['GET', '/api/reports/adjustments/export'],
        ];
    }

    #[DataProvider('endpointCanDangNhap')]
    public function test_chua_dang_nhap_moi_endpoint_tra_401_dang_json(string $method, string $url): void
    {
        $this->json($method, $url)->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated']);
    }

    #[DataProvider('endpointChiAdmin')]
    public function test_nhan_vien_kho_vao_chuc_nang_cua_admin_bi_chan_403(string $method, string $url): void
    {
        $this->apiNhanVien();

        $this->json($method, $url)->assertStatus(403)->assertExactJson(['message' => 'Forbidden']);
    }

    public function test_nhan_vien_kho_khong_sua_xoa_duoc_ban_ghi_cua_danh_muc_nen(): void
    {
        $this->apiNhanVien();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $nguoi = User::factory()->create();

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->id}")->assertForbidden();

        $this->putJson("/api/suppliers/{$supplier->id}", ['name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/suppliers/{$supplier->id}")->assertForbidden();
        $this->patchJson("/api/suppliers/{$supplier->id}/toggle")->assertForbidden();

        $this->putJson("/api/warehouses/{$warehouse->id}", ['code' => 'KHO-X', 'name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/warehouses/{$warehouse->id}")->assertForbidden();
        $this->patchJson("/api/warehouses/{$warehouse->id}/toggle")->assertForbidden();

        $this->putJson("/api/products/{$product->id}", ['name' => 'Đổi tên'])->assertForbidden();
        $this->deleteJson("/api/products/{$product->id}")->assertForbidden();
        $this->patchJson("/api/products/{$product->id}/toggle")->assertForbidden();

        $this->getJson("/api/users/{$nguoi->id}")->assertForbidden();
        $this->putJson("/api/users/{$nguoi->id}", ['name' => 'Đổi tên', 'role' => 'admin'])->assertForbidden();
        $this->patchJson("/api/users/{$nguoi->id}/toggle")->assertForbidden();
        $this->postJson("/api/users/{$nguoi->id}/reset-password")->assertForbidden();

        // Không có thay đổi nào chạm tới CSDL
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name, 'is_active' => 1]);
        $this->assertDatabaseHas('users', ['id' => $nguoi->id, 'role' => 'staff']);
    }

    public function test_nhan_vien_kho_doc_duoc_danh_muc_ton_kho_va_dashboard(): void
    {
        $this->apiNhanVien();

        foreach ([
            '/api/dashboard/summary',
            '/api/categories',
            '/api/suppliers',
            '/api/warehouses',
            '/api/products',
            '/api/inventory',
            '/api/inventory/low-stock',
            '/api/inventory/total-value',
            '/api/imports',
            '/api/exports',
            '/api/adjustments',
            '/api/profile',
        ] as $url) {
            $this->getJson($url)->assertOk();
        }
    }

    public function test_admin_vao_duoc_quan_tri_nguoi_dung_va_nhat_ky(): void
    {
        $this->apiAdmin();

        $this->getJson('/api/users')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/activity-logs')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/products/check-sku?sku=SP-001')->assertOk();
    }

    public function test_tai_khoan_bi_khoa_giua_chung_bi_chan_o_moi_endpoint(): void
    {
        $user = $this->apiAdmin();
        $user->forceFill(['status' => 'locked'])->save();

        $this->getJson('/api/categories')->assertStatus(403)->assertExactJson(['message' => 'Account is locked']);
    }
}
