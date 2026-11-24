<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Livewire\Reports\StockSummaryReport;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * UC05.02 (báo cáo Nhập – Xuất – Tồn) và UC05.04 (xuất Excel) — ReportService, trang web (Livewire) và tải tệp.
 */
class WebStockSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $kho1;

    private Warehouse $kho2;

    private Product $sp1;

    private Product $sp2;

    private Category $danhMucA;

    /**
     * Dữ liệu mẫu (ghi qua InventoryService::applyMovement nên thẻ kho đúng nghiệp vụ):
     *   SP-0001 (kho 1): 20/08 nhập 200 (giá vốn 4.000) | 05/09 nhập 480 (4.200) | 10/09 xuất 360
     *   SP-0002 (kho 1): 25/08 nhập 120 (7.300) | 12/09 điều chỉnh −5 | 13/09 chuyển 30 sang kho 2 (hai dòng sổ)
     *   SP-0003: chỉ có biến động SAU kỳ báo cáo; SP-0004: chưa có biến động nào
     */
    private function dungDuLieuMau(): User
    {
        $admin = $this->dangNhapAdmin();
        $this->kho1 = Warehouse::factory()->create(['name' => 'Kho chinh']);
        $this->kho2 = Warehouse::factory()->create(['name' => 'Kho phu']);
        $this->danhMucA = Category::factory()->create(['name' => 'Danh muc A']);
        $this->sp1 = Product::factory()->create(['sku' => 'SP-0001', 'name' => 'Nuoc suoi', 'category_id' => Category::factory()]);
        $this->sp2 = Product::factory()->create(['sku' => 'SP-0002', 'name' => 'Nuoc ngot', 'category_id' => $this->danhMucA->id]);
        $sp3 = Product::factory()->create(['sku' => 'SP-0003']);
        Product::factory()->create(['sku' => 'SP-0004']);

        $this->luc2('2026-08-20 09:00:00', fn (InventoryService $s) => $s->applyMovement($this->sp1, $this->kho1, MovementType::Import, 200, 'imports', 1, 4000, 4000, $admin));
        $this->luc2('2026-09-05 09:00:00', fn (InventoryService $s) => $s->applyMovement($this->sp1, $this->kho1, MovementType::Import, 480, 'imports', 2, 4200, 4200, $admin));
        $this->luc2('2026-09-10 09:00:00', fn (InventoryService $s) => $s->applyMovement($this->sp1, $this->kho1, MovementType::Export, -360, 'exports', 1, 4200, 4200, $admin));
        $this->luc2('2026-08-25 09:00:00', fn (InventoryService $s) => $s->applyMovement($this->sp2, $this->kho1, MovementType::Import, 120, 'imports', 3, 7300, 7300, $admin));
        $this->luc2('2026-09-12 09:00:00', fn (InventoryService $s) => $s->applyMovement($this->sp2, $this->kho1, MovementType::Adjustment, -5, 'stock_adjustments', 1, 7300, 7300, $admin));
        $this->luc2('2026-09-13 09:00:00', function (InventoryService $s) use ($admin) {
            $s->applyMovement($this->sp2, $this->kho1, MovementType::TransferOut, -30, 'exports', 2, 7300, 7300, $admin);
            $s->applyMovement($this->sp2, $this->kho2, MovementType::TransferIn, 30, 'imports', 4, 7300, 7300, $admin);
        });
        $this->luc2('2026-09-25 09:00:00', fn (InventoryService $s) => $s->applyMovement($sp3, $this->kho1, MovementType::Import, 10, 'imports', 5, 100, 100, $admin));

        $this->luc('10:00'); // trả "bây giờ" về mốc cố định của TestCase

        return $admin;
    }

    /** Chạy một việc với "bây giờ" là thời điểm cho trước (để created_at của sổ đúng ngày mong muốn). */
    private function luc2(string $thoiDiem, callable $viec): void
    {
        Carbon::setTestNow(Carbon::parse($thoiDiem, config('app.timezone')));
        $viec(app(InventoryService::class));
    }

    public function test_tat_ca_kho_chuyen_kho_trieu_tieu_va_co_dong_tong(): void
    {
        $this->dungDuLieuMau();

        $report = app(ReportService::class)->stockSummary(['from' => '2026-09-01', 'to' => '2026-09-20']);

        $this->assertSame(['SP-0001', 'SP-0002'], array_column($report['rows'], 'sku'));

        $this->assertSame(
            ['opening' => 200, 'imports' => 480, 'exports' => 360, 'adjustment' => 0, 'closing' => 320],
            array_intersect_key($report['rows'][0], array_flip(['opening', 'imports', 'exports', 'adjustment', 'closing'])),
        );
        $this->assertSame(1344000.0, $report['rows'][0]['closing_value']);

        // SP-0002: chuyển kho triệt tiêu nên Nhập / Xuất bằng 0, tồn cuối 120 − 5 = 115 (BR05.02-8)
        $this->assertSame(
            ['opening' => 120, 'imports' => 0, 'exports' => 0, 'adjustment' => -5, 'closing' => 115],
            array_intersect_key($report['rows'][1], array_flip(['opening', 'imports', 'exports', 'adjustment', 'closing'])),
        );
        $this->assertSame(839500.0, $report['rows'][1]['closing_value']);

        $this->assertSame(
            ['opening' => 320, 'imports' => 480, 'exports' => 360, 'adjustment' => -5, 'closing' => 435, 'closing_value' => 2183500.0],
            $report['totals'],
        );
    }

    public function test_chon_mot_kho_thi_chuyen_kho_tinh_vao_nhap_xuat(): void
    {
        $this->dungDuLieuMau();
        $params = ['from' => '2026-09-01', 'to' => '2026-09-20'];

        $kho1 = app(ReportService::class)->stockSummary($params + ['warehouse_id' => $this->kho1->id]);
        $sp2 = collect($kho1['rows'])->firstWhere('sku', 'SP-0002');
        $this->assertSame([120, 0, 30, -5, 85], [$sp2['opening'], $sp2['imports'], $sp2['exports'], $sp2['adjustment'], $sp2['closing']]);

        $kho2 = app(ReportService::class)->stockSummary($params + ['warehouse_id' => $this->kho2->id]);
        $this->assertSame(['SP-0002'], array_column($kho2['rows'], 'sku'));
        $this->assertSame([0, 30, 0, 0, 30], [$kho2['rows'][0]['opening'], $kho2['rows'][0]['imports'], $kho2['rows'][0]['exports'], $kho2['rows'][0]['adjustment'], $kho2['rows'][0]['closing']]);
    }

    public function test_loc_theo_danh_muc_va_ky_bao_cao_khac(): void
    {
        $this->dungDuLieuMau();

        $theoDanhMuc = app(ReportService::class)->stockSummary(['from' => '2026-09-01', 'to' => '2026-09-20', 'category_id' => $this->danhMucA->id]);
        $this->assertSame(['SP-0002'], array_column($theoDanhMuc['rows'], 'sku'));

        // Kỳ trước khi có SP-0002 hay SP-0001 phát sinh trong kỳ: chỉ còn các sản phẩm đã có số liệu đến hết ngày "to"
        $kyCu = app(ReportService::class)->stockSummary(['from' => '2026-08-01', 'to' => '2026-08-22']);
        $this->assertSame(['SP-0001'], array_column($kyCu['rows'], 'sku'));
        $this->assertSame(200, $kyCu['rows'][0]['imports']);
        $this->assertSame(0, $kyCu['rows'][0]['opening']);
        $this->assertSame(800000.0, $kyCu['rows'][0]['closing_value']); // 200 × giá vốn 4.000 tại cuối kỳ
    }

    public function test_tham_so_sai_dung_gia_tri_mac_dinh_khong_loi(): void
    {
        $this->dangNhapAdmin();

        $filters = app(ReportService::class)->stockSummary(['from' => 'abc', 'to' => '2026-02-31', 'warehouse_id' => 'x'])['filters'];

        $this->assertSame('2026-11-01', $filters['from']);
        $this->assertSame('2026-11-02', $filters['to']);
        $this->assertNull($filters['warehouse_id']);
    }

    public function test_khach_va_nhan_vien_kho_khong_vao_duoc_bao_cao(): void
    {
        $this->get('/reports/stock-summary')->assertRedirect('/login');

        $this->dangNhapNhanVien();
        $this->get('/reports/stock-summary')->assertForbidden();
        $this->get('/reports/stock-summary/export')->assertForbidden();
    }

    public function test_admin_xem_trang_bao_cao_va_bam_xem_bao_cao(): void
    {
        $this->dungDuLieuMau();

        $this->get('/reports/stock-summary')->assertOk()->assertSee('Báo cáo / Nhập – Xuất – Tồn');

        Livewire::test(StockSummaryReport::class)
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-20')
            ->call('apply')
            ->assertHasNoErrors()
            ->assertSee('SP-0001')
            ->assertSee('SP-0002')
            ->assertSee('1.344.000')
            ->assertSee('2.183.500')
            ->assertSee('Tổng cộng (2 sản phẩm)')
            ->assertSee('01/09/2026')
            ->assertSee('20/09/2026')
            ->set('warehouseId', (string) $this->kho2->id)
            ->call('apply')
            ->assertSee('SP-0002')
            ->assertDontSee('SP-0001');
    }

    public function test_khoang_ngay_sai_bi_chan_o_nut_xem_bao_cao(): void
    {
        $this->dungDuLieuMau();

        Livewire::test(StockSummaryReport::class)
            ->set('from', '2026-09-20')
            ->set('to', '2026-09-01')
            ->call('apply')
            ->assertHasErrors(['to' => 'after_or_equal']);
    }

    public function test_xuat_excel_tra_tep_xlsx_va_ghi_nhat_ky(): void
    {
        $this->dungDuLieuMau();

        $response = $this->get('/reports/stock-summary/export?from=2026-09-01&to=2026-09-20');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('nhap-xuat-ton_2026-09-01_2026-09-20.xlsx', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $response->getContent()); // tệp .xlsx là một tệp zip

        $this->assertSame(1, ActivityLog::query()->where('action', 'report_exported')->count());
    }

    public function test_bao_cao_rong_khong_xuat_duoc_excel(): void
    {
        $admin = $this->dangNhapAdmin();

        $this->get('/reports/stock-summary/export')->assertNotFound();
        $this->assertSame(0, ActivityLog::query()->where('action', 'report_exported')->count());

        $this->expectException(NotFoundHttpException::class);
        app(ReportService::class)->buildExport('stock-summary', [], $admin);
    }
}
