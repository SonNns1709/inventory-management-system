<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\AdjustmentReason;
use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\StockAdjustmentFilter;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        return DB::transaction(function () use ($actor, $data): StockAdjustment {
            $warehouseId = (int) $data['warehouse_id'];
            $scope = $data['scope'] ?? 'all';
            $adjustmentDate = $data['adjustment_date'] ?? now()->toDateString();

            $products = Product::query()->where('is_active', true);
            if ($scope === 'category') {
                $products->where('category_id', (int) $data['category_id']);
            } elseif ($scope === 'products') {
                $products->whereIn('id', array_values($data['product_ids'] ?? []));
            }

            $productIds = $products->pluck('id');
            $stockQuery = Inventory::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('product_id', $productIds);

            if ($scope === 'all') {
                $stockQuery->where('quantity', '>', 0);
            }

            $stock = $stockQuery->pluck('quantity', 'product_id');

            if ($scope === 'all') {
                $productIds = $stock->keys();
            }

            if ($productIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'scope' => 'Phạm vi kiểm kê không có sản phẩm phù hợp.',
                ]);
            }

            $adjustment = StockAdjustment::create([
                'code' => $this->numbers->next('adjustment', Carbon::parse($adjustmentDate)),
                'warehouse_id' => $warehouseId,
                'adjustment_date' => $adjustmentDate,
                'status' => DocumentStatus::Draft,
                'note' => $data['note'] ?? null,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($productIds as $productId) {
                $systemQty = (int) ($stock[$productId] ?? 0);
                $product = Product::query()->findOrFail($productId);

                StockAdjustmentItem::create([
                    'adjustment_id' => $adjustment->getKey(),
                    'product_id' => $productId,
                    'system_qty' => $systemQty,
                    'counted_qty' => $systemQty,
                    'diff_qty' => 0,
                    'reason' => null,
                    'unit_cost' => (string) $product->avg_cost,
                    'diff_value' => 0,
                    'note' => null,
                ]);
            }

            $this->logs->record(
                ActivityAction::AdjustmentCreated,
                $adjustment,
                "Lập phiếu kiểm kê {$adjustment->code}",
                ['new' => $this->snapshot($adjustment)],
                $actor,
            );

            return $this->getDetail($adjustment->getKey());
        });
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
        return DB::transaction(function () use ($adjustment, $data): StockAdjustment {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            if (! $adjustment->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được lưu số đếm');
            }

            $old = $this->snapshot($adjustment);
            $errors = [];

            foreach ($data['items'] as $index => $row) {
                $item = StockAdjustmentItem::query()
                    ->where('adjustment_id', $adjustment->getKey())
                    ->where('product_id', (int) $row['product_id'])
                    ->first();

                if (! $item) {
                    $errors["items.$index.product_id"] = 'Sản phẩm không thuộc phiếu kiểm kê này.';
                    continue;
                }

                $counted = (int) $row['counted_qty'];
                $diff = $counted - (int) $item->system_qty;
                if ($diff !== 0 && empty($row['reason'])) {
                    $errors["items.$index.reason"] = 'Bắt buộc chọn lý do khi số đếm khác số hệ thống.';
                    continue;
                }

                $diffValue = round($diff * (float) $item->unit_cost, 2);
                $item->forceFill([
                    'counted_qty' => $counted,
                    'diff_qty' => $diff,
                    'reason' => $diff === 0 ? null : ($row['reason'] ?? null),
                    'diff_value' => number_format($diffValue, 2, '.', ''),
                    'note' => $row['note'] ?? null,
                ])->save();
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $adjustment->forceFill(['note' => $data['note'] ?? null])->save();
            $this->logs->record(
                ActivityAction::AdjustmentUpdated,
                $adjustment,
                "Lưu số đếm phiếu kiểm kê {$adjustment->code}",
                ['old' => $old, 'new' => $this->snapshot($adjustment)],
            );

            return $this->getDetail($adjustment->getKey());
        });
    }

    /** UC04.03 (bảng API #49) — TODO (Sprint 3): đọc lại tồn hiện tại, cập nhật system_qty của từng dòng draft rồi tính lại diff_qty / diff_value; ghi AdjustmentRefreshed. */
    public function refresh(StockAdjustment $adjustment): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment): StockAdjustment {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            if (! $adjustment->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được làm mới số liệu');
            }

            foreach ($adjustment->items()->lockForUpdate()->get() as $item) {
                $currentQty = (int) Inventory::query()
                    ->where('warehouse_id', $adjustment->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->value('quantity');

                $diff = (int) $item->counted_qty - $currentQty;
                $item->forceFill([
                    'system_qty' => $currentQty,
                    'diff_qty' => $diff,
                    'diff_value' => number_format(round($diff * (float) $item->unit_cost, 2), 2, '.', ''),
                    'reason' => $diff === 0 ? null : $item->reason,
                ])->save();
            }

            $this->logs->record(
                ActivityAction::AdjustmentRefreshed,
                $adjustment,
                "Làm mới số liệu phiếu kiểm kê {$adjustment->code}",
                ['new' => $this->snapshot($adjustment)],
            );

            return $this->getDetail($adjustment->getKey());
        });
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
        return DB::transaction(function () use ($adjustment, $actor): StockAdjustment {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            if (! $adjustment->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được xác nhận');
            }

            foreach ($adjustment->items()->lockForUpdate()->get() as $item) {
                $currentQty = (int) Inventory::query()
                    ->where('warehouse_id', $adjustment->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->value('quantity');

                if ($currentQty !== (int) $item->system_qty) {
                    throw new BusinessConflictException('Tồn kho đã thay đổi sau khi lập phiếu. Hãy làm mới số liệu trước khi xác nhận.');
                }
            }

            foreach ($adjustment->items()->get() as $item) {
                $diff = (int) $item->diff_qty;
                if ($diff === 0) {
                    continue;
                }

                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $this->inventory->applyMovement(
                    $product,
                    $adjustment->warehouse_id,
                    MovementType::Adjustment,
                    $diff,
                    'stock_adjustments',
                    (int) $adjustment->getKey(),
                    (string) $item->unit_cost,
                    (string) $product->avg_cost,
                    $actor,
                );
            }

            $adjustment->forceFill([
                'status' => DocumentStatus::Confirmed,
                'confirmed_by' => $actor->getKey(),
                'confirmed_at' => now(),
            ])->save();

            $this->logs->record(
                ActivityAction::AdjustmentConfirmed,
                $adjustment,
                "Xác nhận phiếu kiểm kê {$adjustment->code}",
                ['new' => $this->snapshot($adjustment)],
                $actor,
            );

            return $this->getDetail($adjustment->getKey());
        });
    }

    /** UC04.03 — hủy phiếu kiểm kê nháp. TODO (Sprint 3): như ImportService::cancel(); ghi AdjustmentCancelled. */
    public function cancel(StockAdjustment $adjustment, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor): StockAdjustment {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->getKey())->lockForUpdate()->firstOrFail();
            if (! $adjustment->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được hủy');
            }

            $adjustment->forceFill(['status' => DocumentStatus::Cancelled])->save();
            $this->logs->record(
                ActivityAction::AdjustmentCancelled,
                $adjustment,
                "Hủy phiếu kiểm kê {$adjustment->code}",
                ['new' => $this->snapshot($adjustment)],
                $actor,
            );

            return $this->getDetail($adjustment->getKey());
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(StockAdjustment $adjustment): array
    {
        $adjustment->loadMissing('items');

        return [
            'id' => $adjustment->getKey(),
            'code' => $adjustment->code,
            'warehouse_id' => $adjustment->warehouse_id,
            'adjustment_date' => $adjustment->adjustment_date->toDateString(),
            'status' => $adjustment->status instanceof \BackedEnum ? $adjustment->status->value : (string) $adjustment->status,
            'note' => $adjustment->note,
            'items' => $adjustment->items->map(fn (StockAdjustmentItem $item) => [
                'product_id' => $item->product_id,
                'system_qty' => $item->system_qty,
                'counted_qty' => $item->counted_qty,
                'diff_qty' => $item->diff_qty,
                'reason' => $item->reason instanceof \BackedEnum ? $item->reason->value : $item->reason,
                'unit_cost' => (string) $item->unit_cost,
                'diff_value' => (string) $item->diff_value,
                'note' => $item->note,
            ])->all(),
        ];
    }
}
