<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Lược đồ CSDL — báo cáo Nhóm 11, Chương 3 §II (Data Dictionary, 15 bảng).
 *
 * Test chạy trên SQLite trong bộ nhớ nên chỉ kiểm được những gì SQLite hỗ trợ: bảng, cột, khóa duy nhất và hành vi RESTRICT của
 * khóa ngoại. TÊN khóa ngoại (fk_products_category...) và ràng buộc CHECK (quantity >= 0) chỉ có trên MySQL 8 — việc đó do job
 * "migrate-mysql" trong .github/workflows/ci.yml đảm nhiệm (migrate:fresh --seed rồi migrate:rollback trên MySQL thật).
 */
class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** 15 bảng nghiệp vụ theo báo cáo, đúng thứ tự trình bày ở Chương 3 §II.1. */
    private const BANG_NGHIEP_VU = [
        'users', 'categories', 'suppliers', 'warehouses', 'products',
        'imports', 'import_items', 'exports', 'export_items',
        'stock_adjustments', 'stock_adjustment_items',
        'inventories', 'stock_movements', 'activity_logs', 'document_sequences',
    ];

    public function test_du_15_bang_nghiep_vu_theo_bao_cao(): void
    {
        $this->assertCount(15, self::BANG_NGHIEP_VU);

        foreach (self::BANG_NGHIEP_VU as $bang) {
            $this->assertTrue(Schema::hasTable($bang), "Thiếu bảng {$bang}");
        }
    }

    public function test_co_bang_ha_tang_cua_laravel_va_sanctum(): void
    {
        // Không tính vào 15 bảng của báo cáo: đây là bảng của framework (phiên, cache, hàng đợi) và của Sanctum (token API)
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens'] as $bang) {
            $this->assertTrue(Schema::hasTable($bang), "Thiếu bảng hạ tầng {$bang}");
        }
    }

    public function test_cac_cot_chinh_theo_data_dictionary(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'name', 'email', 'password', 'role', 'status', 'phone', 'must_change_password', 'last_login_at',
        ]));
        $this->assertTrue(Schema::hasColumns('products', [
            'sku', 'name', 'category_id', 'unit', 'price', 'avg_cost', 'min_stock_level', 'description', 'is_active',
        ]));
        $this->assertTrue(Schema::hasColumns('inventories', ['product_id', 'warehouse_id', 'quantity']));
        $this->assertTrue(Schema::hasColumns('stock_movements', [
            'product_id', 'warehouse_id', 'type', 'reference_type', 'reference_id',
            'qty_change', 'balance_after', 'unit_cost', 'avg_cost_after', 'created_by', 'created_at',
        ]));
        $this->assertTrue(Schema::hasColumns('document_sequences', ['prefix', 'seq_date', 'last_number']));
    }

    public function test_stock_movements_chi_ghi_them_nen_khong_co_cot_updated_at(): void
    {
        // Sổ biến động (thẻ kho) chỉ ghi thêm, không sửa — chỉ có created_at
        $this->assertFalse(Schema::hasColumn('stock_movements', 'updated_at'));
    }

    public function test_khong_xoa_duoc_danh_muc_con_san_pham_o_tang_csdl(): void
    {
        // Khóa ngoại products.category_id -> categories.id ở chế độ RESTRICT: lớp bảo vệ cuối cùng ngoài BR02.01-3 của Service
        $danhMuc = Category::factory()->create();
        Product::factory()->create(['category_id' => $danhMuc->id]);

        $this->expectException(QueryException::class);

        DB::table('categories')->where('id', $danhMuc->id)->delete();
    }

    public function test_moi_cap_san_pham_kho_chi_co_mot_dong_ton(): void
    {
        $ton = Inventory::factory()->create();

        $this->expectException(QueryException::class);

        Inventory::factory()->create([
            'product_id' => $ton->product_id,
            'warehouse_id' => $ton->warehouse_id,
        ]);
    }

    public function test_sku_san_pham_la_duy_nhat(): void
    {
        Product::factory()->create(['sku' => 'DT-0001']);

        $this->expectException(QueryException::class);

        Product::factory()->create(['sku' => 'DT-0001']);
    }
}
