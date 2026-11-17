<?php

namespace Tests\Feature;

use App\Livewire\Reports\AdjustmentReport;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * UC05.03 (báo cáo kiểm kê / điều chỉnh) và UC05.04 (xuất Excel) — ReportService::adjustmentReport, trang web (Livewire) và tải tệp.
 */
class WebAdjustmentReportTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $kho1;

    private Warehouse $kho2;

    /**
     * Dữ liệu mẫu (chèn thẳng vào bảng vì phiếu kiểm kê thật chưa có chức năng lập, Sprint 3):
     *   KK-0001 (đã xác nhận, kho 1, 10/09): SP-1 hệ thống 100 / đếm 95 (−5, hao hụt, giá vốn 4.200) | SP-2 50 / 50 (khớp) | SP-3 10 / 12 (+2, khác, giá vốn 100)
     *   KK-0002 (đã xác nhận, kho 2, 15/09): SP-1 20 / 18 (−2, hư hỏng, giá vốn 4.200)
     *   KK-0003 (NHÁP, kho 1, 12/09): SP-1 −9    KK-0004 (đã xác nhận, kho 1, 01/08 — ngoài kỳ)
     */
    private function dungDuLieuMau(): void
    {
        $admin = $this->dangNhapAdmin(['name' => 'Admin Mot']);
        $this->kho1 = Warehouse::factory()->create(['name' => 'Kho chinh']);
        $this->kho2 = Warehouse::factory()->create(['name' => 'Kho phu']);
        $sp1 = Product::factory()->create(['sku' => 'SP-0001', 'name' => 'Nuoc suoi']);
        $sp2 = Product::factory()->create(['sku' => 'SP-0002', 'name' => 'Nuoc ngot']);
        $sp3 = Product::factory()->create(['sku' => 'SP-0003', 'name' => 'Mi goi']);

        $phieu = function (string $code, Warehouse $kho, string $ngay, string $status, array $dong) use ($admin): void {
            $id = DB::table('stock_adjustments')->insertGetId([
                'code' => $code, 'warehouse_id' => $kho->id, 'adjustment_date' => $ngay, 'status' => $status,
                'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($dong as [$sp, $he, $dem, $lyDo, $giaVon]) {
                DB::table('stock_adjustment_items')->insert([
                    'adjustment_id' => $id, 'product_id' => $sp->id, 'system_qty' => $he, 'counted_qty' => $dem,
                    'diff_qty' => $dem - $he, 'reason' => $lyDo, 'unit_cost' => $giaVon, 'diff_value' => ($dem - $he) * $giaVon,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        };

        $phieu('KK-0001', $this->kho1, '2026-09-10', 'confirmed', [[$sp1, 100, 95, 'shrinkage', 4200], [$sp2, 50, 50, null, 7300], [$sp3, 10, 12, 'other', 100]]);
        $phieu('KK-0002', $this->kho2, '2026-09-15', 'confirmed', [[$sp1, 20, 18, 'damaged', 4200]]);
        $phieu('KK-0003', $this->kho1, '2026-09-12', 'draft', [[$sp1, 30, 21, 'loss', 4200]]);
        $phieu('KK-0004', $this->kho1, '2026-08-01', 'confirmed', [[$sp1, 10, 9, 'loss', 4200]]);
    }

    private function bao(array $params = []): array
    {
        return app(ReportService::class)->adjustmentReport($params + ['from' => '2026-09-01', 'to' => '2026-09-30']);
    }

    public function test_chi_tinh_phieu_da_xac_nhan_trong_ky_va_tong_hop_dung(): void
    {
        $this->dungDuLieuMau();

        $report = $this->bao();

        // 3 dòng lệch (dòng khớp SP-0002 không liệt kê); phiếu nháp KK-0003 và phiếu ngoài kỳ KK-0004 bị loại (BR05.03-3, 05.03-7)
        $this->assertSame(['KK-0001', 'KK-0001', 'KK-0002'], array_column($report['rows'], 'code'));
        $this->assertSame(['SP-0001', 'SP-0003', 'SP-0001'], array_column($report['rows'], 'sku'));

        $this->assertSame(
            ['adjustments' => 2, 'lines_total' => 4, 'lines_diff' => 3, 'shortage_qty' => 7, 'shortage_value' => 29400.0, 'surplus_qty' => 2, 'surplus_value' => 200.0, 'net_value' => -29200.0, 'error_rate' => 75.0],
            $report['summary'],
        );

        $dong = $report['rows'][0];
        $this->assertSame([100, 95, -5, -21000.0, 'shrinkage', 'Hao hụt', 'Admin Mot'], [$dong['system_qty'], $dong['counted_qty'], $dong['diff_qty'], $dong['diff_value'], $dong['reason'], $dong['reason_label'], $dong['creator']]);
    }

    public function test_loc_theo_kho_ly_do_va_hien_ca_dong_khop(): void
    {
        $this->dungDuLieuMau();

        $kho1 = $this->bao(['warehouse_id' => $this->kho1->id]);
        $this->assertSame(['SP-0001', 'SP-0003'], array_column($kho1['rows'], 'sku'));
        $this->assertSame([3, 2, 5, 2, 66.67], [$kho1['summary']['lines_total'], $kho1['summary']['lines_diff'], $kho1['summary']['shortage_qty'], $kho1['summary']['surplus_qty'], $kho1['summary']['error_rate']]);

        $hu = $this->bao(['reason' => 'damaged']);
        $this->assertSame(['KK-0002'], array_column($hu['rows'], 'code'));
        $this->assertSame([2, 8400.0, 0, 25.0], [$hu['summary']['shortage_qty'], $hu['summary']['shortage_value'], $hu['summary']['surplus_qty'], $hu['summary']['error_rate']]);

        $tatCa = $this->bao(['only_diff' => '0']);
        $this->assertCount(4, $tatCa['rows']);
        $this->assertSame(75.0, $tatCa['summary']['error_rate']); // chỉ số tổng hợp không đổi khi hiện cả dòng khớp
    }

    public function test_tham_so_sai_dung_mac_dinh_khong_loi(): void
    {
        $this->dangNhapAdmin();

        $filters = app(ReportService::class)->adjustmentReport(['from' => 'abc', 'to' => '2026-02-31', 'reason' => 'khong-co', 'warehouse_id' => 'x'])['filters'];

        $this->assertSame(['2026-11-01', '2026-11-02', null, null, true], [$filters['from'], $filters['to'], $filters['warehouse_id'], $filters['reason'], $filters['only_diff']]);
    }

    public function test_khach_va_nhan_vien_kho_khong_vao_duoc_bao_cao(): void
    {
        $this->get('/reports/adjustments')->assertRedirect('/login');

        $this->dangNhapNhanVien();
        $this->get('/reports/adjustments')->assertForbidden();
        $this->get('/reports/adjustments/export')->assertForbidden();
    }

    public function test_admin_xem_trang_va_bam_xem_bao_cao(): void
    {
        $this->dungDuLieuMau();

        $this->get('/reports/adjustments')->assertOk()->assertSee('Báo cáo / Báo cáo kiểm kê');

        Livewire::test(AdjustmentReport::class)
            ->set('from', '2026-09-01')
            ->set('to', '2026-09-30')
            ->call('apply')
            ->assertHasNoErrors()
            ->assertSee('KK-0001')
            ->assertSee('KK-0002')
            ->assertDontSee('KK-0003')
            ->assertDontSee('KK-0004')
            ->assertSee('Hao hụt')
            ->assertSee('Hư hỏng')
            ->assertSee('Admin Mot')
            ->assertSee('75,00%')
            ->assertSee('3 / 4 dòng kiểm kê lệch')
            ->set('reason', 'damaged')
            ->call('apply')
            ->assertSee('KK-0002')
            ->assertDontSee('KK-0001');
    }

    public function test_khoang_ngay_sai_bi_chan_o_nut_xem_bao_cao(): void
    {
        $this->dungDuLieuMau();

        Livewire::test(AdjustmentReport::class)
            ->set('from', '2026-09-20')
            ->set('to', '2026-09-01')
            ->call('apply')
            ->assertHasErrors(['to' => 'after_or_equal']);
    }

    public function test_xuat_excel_tra_tep_xlsx_va_ghi_nhat_ky(): void
    {
        $this->dungDuLieuMau();

        $response = $this->get('/reports/adjustments/export?from=2026-09-01&to=2026-09-30');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('kiem-ke_2026-09-01_2026-09-30.xlsx', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $response->getContent());
        $this->assertSame(1, ActivityLog::query()->where('action', 'report_exported')->count());
    }

    public function test_bao_cao_rong_khong_xuat_duoc_excel(): void
    {
        $admin = $this->dangNhapAdmin();

        $this->get('/reports/adjustments/export')->assertNotFound();
        $this->assertSame(0, ActivityLog::query()->where('action', 'report_exported')->count());

        $this->expectException(NotFoundHttpException::class);
        app(ReportService::class)->buildExport('adjustments', [], $admin);
    }
}
