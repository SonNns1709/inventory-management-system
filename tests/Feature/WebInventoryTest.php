<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\Role;
use App\Livewire\Inventory\InventoryIndex;
use App\Livewire\Inventory\StockCard;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC04.01, UC04.02 — giao diện web Tồn kho và Thẻ kho (Livewire).
 */
class WebInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_chua_dang_nhap_bi_chuyen_ve_trang_dang_nhap(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
    }

    public function test_ca_hai_vai_tro_xem_duoc_ton_kho_va_the_kho(): void
    {
        $product = Product::factory()->create(['sku' => 'SP-0001']);

        foreach ([Role::Admin, Role::Staff] as $role) {
            $this->actingAs($this->taoUser($role));

            $this->get('/inventory')->assertOk();
            $this->get("/inventory/{$product->id}/stock-card")->assertOk()->assertSee('SP-0001');
        }
    }

    public function test_danh_dau_ton_thap_theo_tong_ton_moi_kho(): void
    {
        $this->dangNhapNhanVien();
        $khoA = Warehouse::factory()->create(['name' => 'Kho A']);
        $khoB = Warehouse::factory()->create(['name' => 'Kho B']);

        // Thiếu: tổng 45 < ngưỡng 60 (giá trị tồn 45 × 7.300 = 328.500)
        $thieu = Product::factory()->minStock(60)->avgCost('7300')->create(['sku' => 'SP-THIEU']);
        Inventory::factory()->quantity(45)->create(['product_id' => $thieu->id, 'warehouse_id' => $khoA->id]);

        // Đủ: từng kho riêng lẻ (20 và 90) đều dưới ngưỡng 100, nhưng TỔNG 110 >= 100 (BR04.04-3)
        $du = Product::factory()->minStock(100)->create(['sku' => 'SP-DU']);
        Inventory::factory()->quantity(20)->create(['product_id' => $du->id, 'warehouse_id' => $khoA->id]);
        Inventory::factory()->quantity(90)->create(['product_id' => $du->id, 'warehouse_id' => $khoB->id]);

        $html = Livewire::test(InventoryIndex::class)
            ->assertSee('SP-THIEU')
            ->assertSee('SP-DU')
            ->assertSee('328.500')
            ->html();

        $this->assertSame(1, substr_count($html, 'badge-danger'));
        $this->assertSame(2, substr_count($html, 'badge-success'));

        Livewire::test(InventoryIndex::class)
            ->set('lowOnly', true)
            ->assertSee('SP-THIEU')
            ->assertDontSee('SP-DU');
    }

    public function test_loc_theo_kho_va_tu_khoa(): void
    {
        $this->dangNhapNhanVien();
        $khoA = Warehouse::factory()->create();
        $khoB = Warehouse::factory()->create();
        $may = Product::factory()->create(['sku' => 'SP-MAY', 'name' => 'Máy in']);
        $giay = Product::factory()->create(['sku' => 'SP-GIAY', 'name' => 'Giấy A4']);
        Inventory::factory()->quantity(5)->create(['product_id' => $may->id, 'warehouse_id' => $khoA->id]);
        Inventory::factory()->quantity(7)->create(['product_id' => $giay->id, 'warehouse_id' => $khoB->id]);

        Livewire::test(InventoryIndex::class)
            ->set('warehouseId', (string) $khoB->id)
            ->assertSee('SP-GIAY')
            ->assertDontSee('SP-MAY')
            ->set('warehouseId', '')
            ->set('keyword', 'Máy')
            ->assertSee('SP-MAY')
            ->assertDontSee('SP-GIAY');
    }

    public function test_the_kho_hien_cac_bien_dong_va_ton_dau_ky(): void
    {
        $admin = $this->dangNhapAdmin();
        $kho = Warehouse::factory()->create(['name' => 'Kho chính']);
        $product = Product::factory()->create(['sku' => 'SP-0002']);

        $inventory = app(InventoryService::class);
        $inventory->applyMovement($product, $kho, MovementType::Import, 120, 'imports', 1, '1000', '1000', $admin);
        $inventory->applyMovement($product, $kho, MovementType::Export, -45, 'exports', 1, '1000', '1000', $admin);

        Livewire::test(StockCard::class, ['product' => $product])
            ->assertSee('Nhập kho')
            ->assertSee('Xuất kho')
            ->assertSee('+120')
            ->assertSee('-45')
            // Chọn ngày bắt đầu sau ngày phát sinh (02/11/2026): không còn dòng nào, chỉ còn dòng "Tồn đầu kỳ"
            ->set('from', '2026-11-03')
            ->call('apply')
            ->assertSee('Tồn đầu kỳ 03/11/2026')
            ->assertSee('Không có biến động');
    }

    public function test_the_kho_bo_qua_ngay_sai_tren_url(): void
    {
        $this->dangNhapAdmin();
        $product = Product::factory()->create();

        // ?from=abc do người dùng tự gõ không được làm trang lỗi 500
        $this->get("/inventory/{$product->id}/stock-card?from=abc&to=2026-02-31")->assertOk();
    }
}
