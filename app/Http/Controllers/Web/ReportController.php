<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Báo cáo — giao diện web. Chỉ Admin (BR05.02-1, BR05.04-1): route nằm trong nhóm middleware role:admin và Gate `view-reports`
 * (AppServiceProvider) kiểm tra thêm một lần nữa. Báo cáo chỉ tra cứu, không thay đổi dữ liệu (BR05.02-9, BR05.04-8).
 * Hai trang báo cáo do Livewire component dựng (StockSummaryReport, AdjustmentReport); nút "Xuất Excel" là một liên kết tải tệp
 * tới exportStockSummary() / exportAdjustments() (cùng ReportService::buildExport() với API #53, #55).
 */
class ReportController extends Controller
{
    public function stockSummary(): View
    {
        $this->authorize('view-reports');

        return view('reports.stock-summary');
    }

    /** GET /reports/stock-summary/export?from=&to=&warehouse=&category= — tải .xlsx khớp bộ lọc đang xem (BR05.04-4). */
    public function exportStockSummary(Request $request, ReportService $reports): Response
    {
        $this->authorize('view-reports');

        return $reports->buildExport('stock-summary', [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'warehouse_id' => $request->query('warehouse'),
            'category_id' => $request->query('category'),
        ], $request->user());
    }

    /** UC05.03 — Báo cáo kiểm kê (lịch sử kiểm kê / điều chỉnh). */
    public function adjustments(): View
    {
        $this->authorize('view-reports');

        return view('reports.adjustments');
    }

    /** GET /reports/adjustments/export?from=&to=&warehouse=&reason=&diff= — tải .xlsx khớp bộ lọc đang xem (BR05.04-4). */
    public function exportAdjustments(Request $request, ReportService $reports): Response
    {
        $this->authorize('view-reports');

        return $reports->buildExport('adjustments', [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'warehouse_id' => $request->query('warehouse'),
            'reason' => $request->query('reason'),
            'only_diff' => $request->query('diff', '1'),
        ], $request->user());
    }
}
