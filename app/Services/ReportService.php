<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\MovementType;
use App\Exceptions\NotImplementedException;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\SimpleXlsx;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Báo cáo — UC05.02 (Nhập – Xuất – Tồn), UC05.03 (lịch sử kiểm kê / điều chỉnh), UC05.04 (xuất Excel). Chỉ Admin.
 * Báo cáo chỉ đọc và tổng hợp, không bao giờ thay đổi dữ liệu (BR05.02-9, BR05.03-6, BR05.04-8).
 *
 * TRẠNG THÁI: UC05.02 (stockSummary) và UC05.04 cho báo cáo Nhập – Xuất – Tồn (buildExport('stock-summary')) đã cài đặt.
 * UC05.03 (adjustmentReport và buildExport('adjustments')) vẫn là khung, ném NotImplementedException (HTTP 501) kèm hướng dẫn.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR05.02-2: báo cáo Nhập – Xuất – Tồn lọc theo khoảng thời gian, kho và danh mục (bảng API #52 mới liệt kê thời gian + kho nên cần thêm `category_id`).
 *  - BR05.02-4: chỉ tiêu gồm Tồn đầu, Nhập, Xuất, Điều chỉnh, Tồn cuối.
 *  - BR05.03-2: báo cáo kiểm kê / điều chỉnh lọc theo khoảng thời gian, kho, lý do chênh lệch.
 *  - BR05.04-2, BR05.04-5: Admin chỉ xuất Excel hai báo cáo trên, chỉ khi báo cáo có dữ liệu; tệp xuất định dạng .xlsx.
 */
class ReportService
{
    public function __construct(
        private readonly CostingService $costing,
        private readonly ActivityLogService $logs,
    ) {}

    /**
     * UC05.02 — báo cáo Nhập – Xuất – Tồn (bảng API #52). Tham số: from, to (YYYY-MM-DD), warehouse_id, category_id.
     * Mặc định (không truyền hoặc truyền sai): từ ngày 1 của tháng hiện tại đến hôm nay, mọi kho, mọi danh mục.
     *
     * Tổng hợp từ stock_movements theo từng sản phẩm (BR05.02-3):
     *  - tồn đầu = Σ qty_change của các biến động TRƯỚC ngày `from`;
     *  - nhập = Σ qty_change của import (và transfer_in khi chọn một kho); xuất = Σ |qty_change| của export (và transfer_out khi chọn một kho);
     *  - điều chỉnh = Σ qty_change của adjustment (có dấu: âm là hao hụt, dương là dôi thừa);
     *  - tồn cuối = Σ qty_change đến hết ngày `to` = tồn đầu + nhập − xuất + điều chỉnh (BR05.02-5);
     *  - BR05.02-7: chọn một kho thì chuyển kho tính vào Nhập / Xuất của kho đó; BR05.02-8: xem tất cả kho thì hai chiều chuyển kho
     *    triệt tiêu nhau nên không đưa vào Nhập / Xuất (tồn cuối vẫn đúng vì tổng hai chiều bằng 0);
     *  - BR05.02-6: giá trị tồn cuối = tồn cuối × giá vốn bình quân của sản phẩm TẠI cuối kỳ (`avg_cost_after` của biến động cuối cùng
     *    đến hết ngày `to`), kèm dòng tổng cộng. Giá trị chỉ để hiển thị.
     * Sản phẩm không có số liệu nào trong kỳ (cả năm chỉ tiêu đều 0) không hiện trong báo cáo.
     *
     * @param  array<string, mixed>  $params
     * @return array{
     *     filters: array{from: string, to: string, warehouse_id: int|null, category_id: int|null},
     *     rows: list<array{product_id: int, sku: string, name: string, unit: string|null, opening: int, imports: int, exports: int, adjustment: int, closing: int, avg_cost: string, closing_value: float}>,
     *     totals: array{opening: int, imports: int, exports: int, adjustment: int, closing: int, closing_value: float}
     * }
     */
    public function stockSummary(array $params = []): array
    {
        $filters = $this->summaryFilters($params);
        $start = $filters['from'].' 00:00:00';
        $end = $filters['to'].' 23:59:59';
        $oneWarehouse = $filters['warehouse_id'] !== null;

        // Chọn một kho: chuyển kho tính vào Nhập / Xuất; tất cả kho: loại chuyển kho ra (BR05.02-7, BR05.02-8)
        $inTypes = $oneWarehouse ? [MovementType::Import->value, MovementType::TransferIn->value] : [MovementType::Import->value];
        $outTypes = $oneWarehouse ? [MovementType::Export->value, MovementType::TransferOut->value] : [MovementType::Export->value];
        $marks = fn (array $types): string => implode(',', array_fill(0, count($types), '?'));

        $query = DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->where('m.created_at', '<=', $end)
            ->when($oneWarehouse, fn ($q) => $q->where('m.warehouse_id', $filters['warehouse_id']))
            ->when($filters['category_id'] !== null, fn ($q) => $q->where('p.category_id', $filters['category_id']))
            ->groupBy('p.id', 'p.sku', 'p.name', 'p.unit', 'p.avg_cost')
            ->orderBy('p.sku')
            ->selectRaw(
                'p.id, p.sku, p.name, p.unit, p.avg_cost,'
                .' COALESCE(SUM(CASE WHEN m.created_at < ? THEN m.qty_change ELSE 0 END), 0) AS opening,'
                .' COALESCE(SUM(CASE WHEN m.created_at >= ? AND m.type IN ('.$marks($inTypes).') THEN m.qty_change ELSE 0 END), 0) AS imports,'
                .' COALESCE(-SUM(CASE WHEN m.created_at >= ? AND m.type IN ('.$marks($outTypes).') THEN m.qty_change ELSE 0 END), 0) AS exports,'
                .' COALESCE(SUM(CASE WHEN m.created_at >= ? AND m.type = ? THEN m.qty_change ELSE 0 END), 0) AS adjustment,'
                .' COALESCE(SUM(m.qty_change), 0) AS closing,'
                .' (SELECT m2.avg_cost_after FROM stock_movements m2 WHERE m2.product_id = p.id AND m2.created_at <= ?'
                .' ORDER BY m2.created_at DESC, m2.id DESC LIMIT 1) AS cost_at_end',
                [$start, $start, ...$inTypes, $start, ...$outTypes, $start, MovementType::Adjustment->value, $end],
            );

        $rows = [];
        $totals = ['opening' => 0, 'imports' => 0, 'exports' => 0, 'adjustment' => 0, 'closing' => 0, 'closing_value' => 0.0];

        foreach ($query->get() as $r) {
            $row = [
                'product_id' => (int) $r->id,
                'sku' => (string) $r->sku,
                'name' => (string) $r->name,
                'unit' => $r->unit,
                'opening' => (int) $r->opening,
                'imports' => (int) $r->imports,
                'exports' => (int) $r->exports,
                'adjustment' => (int) $r->adjustment,
                'closing' => (int) $r->closing,
            ];

            if ($row['opening'] === 0 && $row['imports'] === 0 && $row['exports'] === 0 && $row['adjustment'] === 0 && $row['closing'] === 0) {
                continue;
            }

            $cost = (string) ($r->cost_at_end ?? $r->avg_cost);
            $row['avg_cost'] = $cost;
            $row['closing_value'] = round($row['closing'] * (float) $cost, 2);
            $rows[] = $row;

            foreach (['opening', 'imports', 'exports', 'adjustment', 'closing', 'closing_value'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        $totals['closing_value'] = round($totals['closing_value'], 2);

        return ['filters' => $filters, 'rows' => $rows, 'totals' => $totals];
    }

    /**
     * UC05.03 — báo cáo lịch sử kiểm kê / điều chỉnh (bảng API #54). Tham số: from, to, warehouse_id, reason.
     *
     * TODO (Sprint 4): chỉ phiếu đã confirmed trong kỳ (BR05.03-3, BR05.03-7); mỗi dòng gồm phiếu, kho, sản phẩm, system_qty, counted_qty,
     * diff_qty, lý do, giá trị chênh lệch (BR05.03-4); tổng hao hụt, tổng dôi thừa, tỷ lệ sai lệch (BR05.03-5).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function adjustmentReport(array $params = []): array
    {
        throw new NotImplementedException('UC05.03 — ReportService::adjustmentReport()');
    }

    /**
     * UC05.04 — xuất báo cáo ra tệp .xlsx (bảng API #53, #55). Đã cài cho $type = 'stock-summary'; 'adjustments' còn là khung.
     *
     * Dữ liệu tạo bằng đúng bộ lọc hiện tại (BR05.04-4); báo cáo rỗng thì KHÔNG tạo tệp mà trả 404 "Không có dữ liệu" (BR05.04-3, BR05.04-6);
     * sau khi tạo tệp thành công ghi Activity Log `report_exported` (BR05.04-7). Tệp dựng bằng App\Support\SimpleXlsx (không cần thư viện ngoài).
     *
     * @param  array<string, mixed>  $params
     */
    public function buildExport(string $type, array $params, User $actor): Response
    {
        if ($type !== 'stock-summary') {
            throw new NotImplementedException('UC05.04 — ReportService::buildExport() cho báo cáo kiểm kê');
        }

        $report = $this->stockSummary($params);

        if ($report['rows'] === []) {
            throw new NotFoundHttpException('Không có dữ liệu để xuất');
        }

        $f = $report['filters'];
        $warehouse = $f['warehouse_id'] !== null ? Warehouse::query()->find($f['warehouse_id']) : null;
        $t = $report['totals'];

        $binary = SimpleXlsx::build(
            'Nhập - Xuất - Tồn',
            ['SKU', 'Sản phẩm', 'Đơn vị', 'Tồn đầu', 'Nhập', 'Xuất', 'Điều chỉnh', 'Tồn cuối', 'Giá vốn BQ cuối kỳ (đ)', 'Giá trị tồn cuối (đ)'],
            array_map(fn (array $r): array => [
                $r['sku'], $r['name'], $r['unit'], $r['opening'], $r['imports'], $r['exports'], $r['adjustment'], $r['closing'],
                (float) $r['avg_cost'], $r['closing_value'],
            ], $report['rows']),
            ['Tổng cộng ('.count($report['rows']).' sản phẩm)', null, null, $t['opening'], $t['imports'], $t['exports'], $t['adjustment'], $t['closing'], null, $t['closing_value']],
            [14, 38, 10, 11, 11, 11, 12, 11, 22, 22],
        );

        $this->logs->record(
            ActivityAction::ReportExported,
            'reports',
            'Xuất Excel báo cáo Nhập – Xuất – Tồn '.$f['from'].' → '.$f['to'].' ('.($warehouse?->name ?? 'tất cả kho').')',
            ['new' => $f],
            $actor,
        );

        $filename = 'nhap-xuat-ton_'.$f['from'].'_'.$f['to'].'.xlsx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    /**
     * Tham số báo cáo do người dùng tự gõ nên có thể sai: chỉ giữ giá trị hợp lệ, phần thiếu / sai lấy mặc định
     * (từ ngày 1 của tháng này đến hôm nay) thay vì báo lỗi 500.
     *
     * @param  array<string, mixed>  $params
     * @return array{from: string, to: string, warehouse_id: int|null, category_id: int|null}
     */
    private function summaryFilters(array $params): array
    {
        $valid = Validator::make($params, [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'warehouse_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
        ])->valid();

        $from = $valid['from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $valid['to'] ?? Carbon::now()->toDateString();

        return [
            'from' => $from,
            'to' => $to < $from ? $from : $to,
            'warehouse_id' => isset($valid['warehouse_id']) ? (int) $valid['warehouse_id'] : null,
            'category_id' => isset($valid['category_id']) ? (int) $valid['category_id'] : null,
        ];
    }
}
