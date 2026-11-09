<?php

namespace App\Services;

use App\Exceptions\NotImplementedException;
use App\Http\Filters\StockAdjustmentFilter;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Kiểm kê và điều chỉnh tồn kho — UC04.03. Báo cáo, Sơ đồ lớp thiết kế (Sơ đồ 73): StockAdjustmentService phụ thuộc
 * InventoryService, DocumentNumberService (các phương thức createSheet, saveCounts, refresh, confirm, cancel).
 *
 * Quy tắc cốt lõi: system_qty là SNAPSHOT lúc lập phiếu (BR04.03-2); diff_qty = counted_qty − system_qty (BR04.03-4);
 * diff_qty ≠ 0 thì bắt buộc có lý do loss | damaged | shrinkage | other (BR04.03-5); kiểm kê là cách DUY NHẤT thay đổi trực tiếp
 * số lượng tồn ngoài phiếu nhập / xuất (BR04.03-7).
 *
 * TRẠNG THÁI KHUNG (Sprint 3): search, getDetail đã chạy; các thao tác ghi đang ném NotImplementedException (HTTP 501).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR04.03-8: phiếu kiểm kê có trạng thái draft / confirmed / cancelled; mặc định khi tạo (createSheet) là draft.
 */
class StockAdjustmentService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DocumentNumberService $numbers,
        private readonly CostingService $costing,
        private readonly ActivityLogService $logs,
    ) {}

    /** @param  array<string, mixed>  $params  keyword, from, to, status, warehouse_id, sort, direction, per_page */
    public function search(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = StockAdjustmentFilter::valid($params);

        return StockAdjustmentFilter::apply(StockAdjustment::query()->with(['warehouse', 'creator']), $filters)
            ->paginate($perPage ?? StockAdjustmentFilter::perPage($filters));
    }

    public function getDetail(int|string $id): StockAdjustment
    {
        return StockAdjustment::query()->with(['warehouse', 'creator', 'confirmer', 'items.product'])->findOrFail($id);
    }

    /**
     * UC04.03 — lập phiếu kiểm kê nháp kèm snapshot (bảng API #47).
     *
     * TODO (Sprint 3): trong transaction — code = DocumentNumberService::next('adjustment'); tạo StockAdjustment (draft);
     * chọn sản phẩm theo scope (all = mọi sản phẩm có tồn ở kho | category | products); với mỗi sản phẩm tạo
     * StockAdjustmentItem {system_qty = tồn hiện tại tại kho, counted_qty = system_qty, diff_qty = 0, unit_cost = products.avg_cost};
     * ghi Activity Log AdjustmentCreated.
     *
     * @param  array<string, mixed>  $data  đã qua StoreStockAdjustmentRequest
     */
    public function createSheet(User $actor, array $data): StockAdjustment
    {
        throw new NotImplementedException('UC04.03 — StockAdjustmentService::createSheet()');
    }

    /**
     * UC04.03 — lưu số đếm thực tế (bảng API #48).
     *
     * TODO (Sprint 3): chỉ khi draft; với mỗi dòng: diff_qty = counted_qty − system_qty, diff_value = diff_qty × unit_cost;
     * nếu diff_qty ≠ 0 mà thiếu reason -> ném ValidationException (422) ở trường items.{i}.reason (BR04.03-5); ghi AdjustmentUpdated.
     *
     * @param  array<string, mixed>  $data  đã qua UpdateStockAdjustmentRequest
     */
    public function saveCounts(StockAdjustment $adjustment, array $data): StockAdjustment
    {
        throw new NotImplementedException('UC04.03 — StockAdjustmentService::saveCounts()');
    }

    /** UC04.03 (bảng API #49) — TODO (Sprint 3): đọc lại tồn hiện tại, cập nhật system_qty của từng dòng draft rồi tính lại diff_qty / diff_value; ghi AdjustmentRefreshed. */
    public function refresh(StockAdjustment $adjustment): StockAdjustment
    {
        throw new NotImplementedException('UC04.03 — StockAdjustmentService::refresh()');
    }

    /**
     * UC04.03 — xác nhận phiếu: điều chỉnh tồn thực tế (bảng API #50).
     *
     * TODO (Sprint 3): trong transaction — khóa phiếu và các dòng tồn liên quan; BR04.03-3: so lại tồn hiện tại với system_qty, nếu đã
     * đổi thì ném BusinessConflictException (409, yêu cầu "refresh" trước); với mỗi dòng có diff_qty ≠ 0:
     * InventoryService::applyMovement(MovementType::Adjustment, diff_qty, 'stock_adjustments', id, avg_cost, avg_cost, $actor);
     * status = confirmed, confirmed_by, confirmed_at; ghi AdjustmentConfirmed (BR04.03-6: chênh lệch + lý do vào thẻ kho).
     */
    public function confirm(StockAdjustment $adjustment, User $actor): StockAdjustment
    {
        throw new NotImplementedException('UC04.03 — StockAdjustmentService::confirm()');
    }

    /** UC04.03 — hủy phiếu kiểm kê nháp. TODO (Sprint 3): như ImportService::cancel(); ghi AdjustmentCancelled. */
    public function cancel(StockAdjustment $adjustment, User $actor): StockAdjustment
    {
        throw new NotImplementedException('UC04.03 — StockAdjustmentService::cancel()');
    }
}
