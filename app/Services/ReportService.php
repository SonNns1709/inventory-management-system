<?php

namespace App\Services;

use App\Exceptions\NotImplementedException;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Báo cáo — UC05.02 (Nhập – Xuất – Tồn), UC05.03 (lịch sử kiểm kê / điều chỉnh), UC05.04 (xuất Excel). Chỉ Admin.
 * Báo cáo chỉ đọc và tổng hợp, không bao giờ thay đổi dữ liệu (BR05.02-9, BR05.03-6, BR05.04-8).
 *
 * TRẠNG THÁI KHUNG (Sprint 4): cả ba phương thức đang ném NotImplementedException (HTTP 501) kèm hướng dẫn.
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
     * UC05.02 — báo cáo Nhập – Xuất – Tồn (bảng API #52). Tham số: from, to, warehouse_id.
     *
     * TODO (Sprint 4): tổng hợp từ stock_movements theo từng sản phẩm (BR05.02-3):
     *   tồn đầu = Σ qty_change trước `from`; nhập = Σ qty_change > 0 của import / transfer_in; xuất = Σ |qty_change| của export / transfer_out;
     *   điều chỉnh = Σ qty_change của adjustment; tồn cuối = tồn đầu + nhập − xuất ± điều chỉnh (BR05.02-5);
     *   giá trị theo giá vốn (BR05.02-6) kèm dòng tổng cộng. Chọn một kho: chuyển kho tính vào Nhập / Xuất của kho đó (BR05.02-7);
     *   xem tất cả kho: hai chiều chuyển kho triệt tiêu nhau (BR05.02-8).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function stockSummary(array $params = []): array
    {
        throw new NotImplementedException('UC05.02 — ReportService::stockSummary()');
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
     * UC05.04 — xuất báo cáo ra tệp .xlsx (bảng API #53, #55).
     *
     * TODO (Sprint 4): $type = 'stock-summary' | 'adjustments'; tạo dữ liệu bằng đúng bộ lọc hiện tại (BR05.04-4); báo cáo rỗng thì KHÔNG
     * tạo tệp (BR05.04-3, BR05.04-6 — trả 404 "Không có dữ liệu" đúng bảng API #53 / #55); dùng maatwebsite/excel hoặc phpoffice/phpspreadsheet
     * (chưa khai báo trong composer.json); sau khi xuất thành công ghi Activity Log ActivityAction::ReportExported (BR05.04-7).
     *
     * @param  array<string, mixed>  $params
     */
    public function buildExport(string $type, array $params, User $actor): Response
    {
        throw new NotImplementedException('UC05.04 — ReportService::buildExport()');
    }
}
