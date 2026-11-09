<?php

namespace App\Services;

use App\Exceptions\NotImplementedException;
use App\Http\Filters\ImportFilter;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Phiếu nhập kho — UC03.01 (lập / xác nhận), UC03.03 (tra cứu), UC03.04 (hủy nháp). Báo cáo, Sơ đồ lớp thiết kế (Sơ đồ 73): ImportService
 * phụ thuộc InventoryService, DocumentNumberService, CostingService.
 *
 * TRẠNG THÁI KHUNG (Sprint 3): tra cứu (search, getDetail) và xem trước giá vốn (previewCost) đã chạy;
 * các thao tác ghi (createDraft, updateDraft, confirm, cancel) đang ném NotImplementedException (HTTP 501) kèm hướng dẫn từng bước.
 * Controller, Form Request, Policy, Resource và route đã nối sẵn — chỉ cần thay dòng `throw` bằng logic thật.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR03.03-7: tra cứu (search, getDetail) chỉ đọc — không làm thay đổi tồn kho hay trạng thái phiếu.
 */
class ImportService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DocumentNumberService $numbers,
        private readonly CostingService $costing,
        private readonly ActivityLogService $logs,
    ) {}

    /** UC03.03 — tra cứu phân trang (BR03.03-2/3). @param array<string, mixed> $params keyword, from, to, status, supplier_id, warehouse_id, sort, direction, per_page */
    public function search(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = ImportFilter::valid($params);

        return ImportFilter::apply(Import::query()->with(['supplier', 'warehouse', 'creator']), $filters)
            ->paginate($perPage ?? ImportFilter::perPage($filters));
    }

    /** UC03.03 / BR03.03-6 — chi tiết phiếu: dòng hàng, nhà cung cấp, kho, người lập, người xác nhận. */
    public function getDetail(int|string $id): Import
    {
        return Import::query()->with(['supplier', 'warehouse', 'creator', 'confirmer', 'items.product'])->findOrFail($id);
    }

    /**
     * UC03.01 Alternative Flow A3 — giá vốn bình quân mới nếu nhập thêm $qtyIn sản phẩm với đơn giá $unitCost.
     * Chỉ tính toán, không ghi gì. Tồn cũ = tổng tồn của sản phẩm trên mọi kho.
     */
    public function previewCost(int $productId, int $qtyIn, string $unitCost): string
    {
        $product = Product::query()->findOrFail($productId);
        $oldQty = (int) Inventory::query()->where('product_id', $productId)->sum('quantity');

        return $this->costing->weightedAverage($oldQty, $product->avg_cost, $qtyIn, $unitCost);
    }

    /**
     * UC03.01 — lập phiếu nhập ở trạng thái draft (BR03.01-1 … BR03.01-3). Chưa được thay đổi tồn kho.
     *
     * TODO (Sprint 3):
     *  1. DB::transaction(...);
     *  2. code = DocumentNumberService::next('import', import_date);
     *  3. tạo Import {code, supplier_id, warehouse_id, import_date, status = draft, note, created_by = $actor};
     *  4. với từng dòng: tạo ImportItem {product_id, quantity, unit_cost, line_total = quantity × unit_cost};
     *  5. total_amount = Σ line_total; ghi Activity Log ImportCreated; trả về getDetail().
     *
     * @param  array<string, mixed>  $data  đã qua StoreImportRequest
     */
    public function createDraft(User $actor, array $data): Import
    {
        throw new NotImplementedException('UC03.01 — ImportService::createDraft()');
    }

    /**
     * UC03.01 — sửa phiếu nháp (bảng API #33).
     *
     * TODO (Sprint 3): chỉ khi status = draft, ngược lại ném BusinessConflictException (409, BR03.04-2);
     * trong transaction: cập nhật đầu phiếu, thay toàn bộ dòng (xóa + tạo lại), tính lại total_amount; ghi Activity Log ImportUpdated.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Import $import, array $data): Import
    {
        throw new NotImplementedException('UC03.01 — ImportService::updateDraft()');
    }

    /**
     * UC03.01 — xác nhận phiếu: cộng tồn, tính lại giá vốn bình quân, ghi thẻ kho (BR03.01-4 … BR03.01-8).
     *
     * TODO (Sprint 3):
     *  1. phiếu phải đang là draft, nếu không -> BusinessConflictException (409);
     *  2. DB::transaction(function () { ... }) — lỗi ở bất kỳ dòng nào sẽ hoàn tác tất cả (không xác nhận một phần, Exception E3):
     *       a. khóa phiếu (lockForUpdate) và đọc lại trạng thái;
     *       b. với từng dòng (sắp theo product_id để tránh deadlock): khóa sản phẩm và các dòng tồn của nó;
     *          $oldQty = tổng tồn mọi kho; $newAvg = CostingService::weightedAverage($oldQty, $product->avg_cost, qty, unit_cost);
     *          cập nhật products.avg_cost = $newAvg; InventoryService::applyMovement(product, warehouse, MovementType::Import,
     *          +qty, 'imports', $import->id, unit_cost, $newAvg, $actor);
     *       c. status = confirmed, confirmed_by = $actor, confirmed_at = now() (BR03.01-7);
     *  3. ghi Activity Log ImportConfirmed; trả về getDetail().
     */
    public function confirm(Import $import, User $actor): Import
    {
        throw new NotImplementedException('UC03.01 — ImportService::confirm()');
    }

    /**
     * UC03.04 — hủy phiếu nháp (BR03.04-1 … BR03.04-6).
     *
     * TODO (Sprint 3): chỉ phiếu draft mới hủy được (confirmed bất biến -> 409); đặt status = cancelled, KHÔNG đụng tới tồn kho;
     * quyền "người lập hoặc Admin" đã do ImportPolicy::cancel kiểm tra; ghi Activity Log ImportCancelled.
     */
    public function cancel(Import $import, User $actor): Import
    {
        throw new NotImplementedException('UC03.04 — ImportService::cancel()');
    }
}
