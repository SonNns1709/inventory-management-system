<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Báo cáo — UC05.02 – UC05.04 (bảng API, nhóm 7: #52 – #55). Chỉ Admin (Gate `view-reports` trong AppServiceProvider).
 * Báo cáo chỉ tra cứu và tổng hợp, không bao giờ thay đổi dữ liệu tồn kho (BR05.02-9, BR05.03-6, BR05.04-8).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - UC05.02 (stock-summary), UC05.03 (adjustments), UC05.04 (các route /export) — chỉ Admin.
 */
class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReportService $reports) {}

    /** GET /api/reports/stock-summary (#52) — Nhập – Xuất – Tồn theo sản phẩm + dòng tổng cộng. Tham số: from, to, warehouse_id. */
    public function stockSummary(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        return $this->success($this->reports->stockSummary($request->query()));
    }

    /** GET /api/reports/stock-summary/export (#53) — tệp .xlsx khớp bộ lọc hiện tại; ghi Activity Log `report_exported`. */
    public function exportStockSummary(Request $request): Response
    {
        $this->authorize('view-reports');

        return $this->reports->buildExport('stock-summary', $request->query(), $request->user());
    }

    /** GET /api/reports/adjustments (#54) — phiếu kiểm kê đã confirmed, tổng hao hụt / dôi thừa, tỷ lệ sai lệch. Tham số: from, to, warehouse_id, reason. */
    public function adjustments(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        return $this->success($this->reports->adjustmentReport($request->query()));
    }

    /** GET /api/reports/adjustments/export (#55) */
    public function exportAdjustments(Request $request): Response
    {
        $this->authorize('view-reports');

        return $this->reports->buildExport('adjustments', $request->query(), $request->user());
    }
}
