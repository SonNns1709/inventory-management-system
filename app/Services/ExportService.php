<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\DocumentStatus;
use App\Enums\ExportReason;
use App\Enums\MovementType;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\ExportFilter;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phiếu xuất kho (bán hàng / chuyển kho) — UC03.02 (lập / xác nhận), UC03.03 (tra cứu), UC03.04 (hủy nháp).
 * Báo cáo, Sơ đồ lớp thiết kế (Sơ đồ 73): ExportService phụ thuộc InventoryService, DocumentNumberService.
 *
 * TRẠNG THÁI KHUNG (Sprint 3): search, getDetail, checkAvailability đã chạy; createDraft, updateDraft, confirm, cancel
 * đang ném NotImplementedException (HTTP 501) kèm hướng dẫn từng bước.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR03.03-7: tra cứu (search, getDetail) chỉ đọc — không làm thay đổi tồn kho hay trạng thái phiếu.
 */
class ExportService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DocumentNumberService $numbers,
        private readonly ActivityLogService $logs,
    ) {}

    /** UC03.03 — tra cứu phân trang. @param array<string, mixed> $params keyword, from, to, status, reason, warehouse_id, sort, direction, per_page */
    public function search(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = ExportFilter::valid($params);

        return ExportFilter::apply(Export::query()->with(['warehouse', 'destWarehouse', 'creator']), $filters)
            ->paginate($perPage ?? ExportFilter::perPage($filters));
    }

    /** UC03.03 / BR03.03-6 — chi tiết phiếu xuất kèm dòng hàng, kho xuất / kho nhận, người lập, người xác nhận. */
    public function getDetail(int|string $id): Export
    {
        return Export::query()->with(['warehouse', 'destWarehouse', 'creator', 'confirmer', 'items.product'])->findOrFail($id);
    }

    /**
     * UC03.02 — kiểm tra tồn khả dụng của từng dòng tại kho xuất (bảng API #37). Chỉ tra cứu, không giữ chỗ hàng.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     * @return array{is_available: bool, details: array<int, array<string, mixed>>}
     */
    public function checkAvailability(int $warehouseId, array $items): array
    {
        $productIds = array_map(fn (array $item): int => (int) $item['product_id'], $items);

        $stock = Inventory::query()->where('warehouse_id', $warehouseId)->whereIn('product_id', $productIds)->pluck('quantity', 'product_id');
        $products = Product::query()->whereIn('id', $productIds)->get(['id', 'sku', 'name'])->keyBy('id');

        $available = true;
        $details = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $requested = (int) $item['quantity'];
            $onHand = (int) ($stock[$productId] ?? 0);
            $enough = $onHand >= $requested;
            $available = $available && $enough;

            $details[] = [
                'product_id' => $productId,
                'sku' => $products[$productId]->sku ?? null,
                'name' => $products[$productId]->name ?? null,
                'requested' => $requested,
                'available' => $onHand,
                'is_enough' => $enough,
                'shortage' => max(0, $requested - $onHand),
            ];
        }

        return ['is_available' => $available, 'details' => $details];
    }

    /**
     * UC03.02 — lập phiếu xuất nháp (BR03.02-1, BR03.02-2, BR03.02-8): không trừ tồn.
     *
     * TODO (Sprint 3): giống ImportService::createDraft nhưng dùng DocumentNumberService::next('export'); lưu reason, dest_warehouse_id
     * (chỉ khi chuyển kho); ExportItem {product_id, quantity, unit_cost = 0, line_total = 0} — giá vốn chỉ chốt lúc xác nhận (BR03.02-5);
     * ghi Activity Log ExportCreated.
     *
     * @param  array<string, mixed>  $data  đã qua StoreExportRequest
     */
    public function createDraft(User $actor, array $data): Export
    {
        return DB::transaction(function () use ($actor, $data): Export {
            $export = Export::create([
                'code' => $this->numbers->next('export', Carbon::parse($data['export_date'])),
                'warehouse_id' => (int) $data['warehouse_id'],
                'reason' => $data['reason'],
                'dest_warehouse_id' => $data['reason'] === ExportReason::Transfer->value
                    ? (int) $data['dest_warehouse_id']
                    : null,
                'export_date' => $data['export_date'],
                'status' => DocumentStatus::Draft,
                'total_cost' => 0,
                'note' => $data['note'] ?? null,
                'created_by' => $actor->getKey(),
            ]);

            $this->replaceItems($export, $data['items']);
            $this->logs->record(
                ActivityAction::ExportCreated,
                $export,
                "Lập phiếu xuất {$export->code}",
                ['new' => $this->exportSnapshot($export)],
                $actor,
            );

            return $this->getDetail($export->getKey());
        });
    }

    /**
     * UC03.02 — sửa phiếu xuất nháp (bảng API #40). TODO (Sprint 3): chỉ khi draft (409 nếu không); thay toàn bộ dòng; ghi ExportUpdated.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Export $export, array $data): Export
    {
        return DB::transaction(function () use ($export, $data): Export {
            $export = Export::query()->whereKey($export->getKey())->lockForUpdate()->firstOrFail();
            if (! $export->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được sửa');
            }

            $old = $this->exportSnapshot($export);
            $export->fill([
                'warehouse_id' => (int) $data['warehouse_id'],
                'reason' => $data['reason'],
                'dest_warehouse_id' => $data['reason'] === ExportReason::Transfer->value
                    ? (int) $data['dest_warehouse_id']
                    : null,
                'export_date' => $data['export_date'],
                'note' => $data['note'] ?? null,
            ])->save();

            $this->replaceItems($export, $data['items']);
            $this->logs->record(
                ActivityAction::ExportUpdated,
                $export,
                "Cập nhật phiếu xuất {$export->code}",
                ['old' => $old, 'new' => $this->exportSnapshot($export)],
            );

            return $this->getDetail($export->getKey());
        });
    }

    /**
     * UC03.02 — xác nhận phiếu xuất: trừ tồn (BR03.02-3 … BR03.02-7).
     *
     * TODO (Sprint 3):
     *  1. phiếu phải đang là draft (409 nếu không);
     *  2. DB::transaction(function () { ... }):
     *       a. khóa phiếu và, với từng dòng (sắp theo product_id), khóa dòng tồn tại kho xuất;
     *       b. thu thập MỌI dòng thiếu hàng; nếu có -> BusinessConflictException (409) liệt kê các dòng, không xác nhận một phần (BR03.02-4);
     *       c. đơn giá vốn của dòng = products.avg_cost TẠI THỜI ĐIỂM XÁC NHẬN (BR03.02-5); line_total = qty × avg_cost;
     *       d. reason = sale: applyMovement(MovementType::Export, −qty, 'exports', id, avg_cost, avg_cost, $actor);
     *          reason = transfer: applyMovement(TransferOut, −qty) ở kho xuất RỒI applyMovement(TransferIn, +qty) ở kho nhận;
     *          giá vốn bình quân của sản phẩm KHÔNG đổi, chỉ dịch chuyển số lượng giữa hai kho (BR03.02-6);
     *       e. total_cost = Σ line_total; status = confirmed, confirmed_by, confirmed_at;
     *  3. ghi Activity Log ExportConfirmed; trả về getDetail().
     */
    public function confirm(Export $export, User $actor): Export
    {
        return DB::transaction(function () use ($export, $actor): Export {
            $export = Export::query()->whereKey($export->getKey())->lockForUpdate()->firstOrFail();
            if (! $export->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được xác nhận');
            }

            $items = $export->items()->orderBy('product_id')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw new BusinessConflictException('Phiếu xuất phải có ít nhất một dòng hàng');
            }

            $shortages = [];
            foreach ($items as $item) {
                $inventory = Inventory::query()
                    ->where('warehouse_id', $export->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                $available = (int) ($inventory?->quantity ?? 0);
                if ($available < (int) $item->quantity) {
                    $product = Product::query()->find($item->product_id);
                    $shortages[] = ($product?->sku ?? ('#'.$item->product_id))
                        .' cần '.(int) $item->quantity
                        .', tồn khả dụng còn '.$available;
                }
            }

            if ($shortages !== []) {
                throw new BusinessConflictException('Không đủ tồn để xác nhận: '.implode('; ', $shortages));
            }

            $totalCost = 0.0;
            foreach ($items as $item) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $cost = (string) $product->avg_cost;
                $lineTotal = round((int) $item->quantity * (float) $cost, 2);

                $item->forceFill([
                    'unit_cost' => $cost,
                    'line_total' => number_format($lineTotal, 2, '.', ''),
                ])->save();
                $totalCost += $lineTotal;

                if ($export->reason === ExportReason::Sale) {
                    $this->inventory->applyMovement(
                        $product,
                        $export->warehouse_id,
                        MovementType::Export,
                        -(int) $item->quantity,
                        'exports',
                        (int) $export->getKey(),
                        $cost,
                        $cost,
                        $actor,
                    );
                } else {
                    $this->inventory->applyMovement(
                        $product,
                        $export->warehouse_id,
                        MovementType::TransferOut,
                        -(int) $item->quantity,
                        'exports',
                        (int) $export->getKey(),
                        $cost,
                        $cost,
                        $actor,
                    );
                    $this->inventory->applyMovement(
                        $product,
                        (int) $export->dest_warehouse_id,
                        MovementType::TransferIn,
                        (int) $item->quantity,
                        'exports',
                        (int) $export->getKey(),
                        $cost,
                        $cost,
                        $actor,
                    );
                }
            }

            $export->forceFill([
                'status' => DocumentStatus::Confirmed,
                'total_cost' => number_format($totalCost, 2, '.', ''),
                'confirmed_by' => $actor->getKey(),
                'confirmed_at' => now(),
            ])->save();

            $this->logs->record(
                ActivityAction::ExportConfirmed,
                $export,
                "Xác nhận phiếu xuất {$export->code}",
                ['new' => $this->exportSnapshot($export)],
                $actor,
            );

            return $this->getDetail($export->getKey());
        });
    }

    /** UC03.04 — hủy phiếu xuất nháp. TODO (Sprint 3): như ImportService::cancel(); ghi Activity Log ExportCancelled. */
    public function cancel(Export $export, User $actor): Export
    {
        return DB::transaction(function () use ($export, $actor): Export {
            $export = Export::query()->whereKey($export->getKey())->lockForUpdate()->firstOrFail();
            if (! $export->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được hủy');
            }

            $export->forceFill(['status' => DocumentStatus::Cancelled])->save();
            $this->logs->record(
                ActivityAction::ExportCancelled,
                $export,
                "Hủy phiếu xuất {$export->code}",
                ['new' => $this->exportSnapshot($export)],
                $actor,
            );

            return $this->getDetail($export->getKey());
        });
    }

    /** @param array<int, array{product_id: int, quantity: int}> $items */
    private function replaceItems(Export $export, array $items): void
    {
        $export->items()->delete();
        foreach ($items as $item) {
            ExportItem::create([
                'export_id' => $export->getKey(),
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'unit_cost' => null,
                'line_total' => null,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function exportSnapshot(Export $export): array
    {
        $export->loadMissing('items');

        return [
            'id' => $export->getKey(),
            'code' => $export->code,
            'warehouse_id' => $export->warehouse_id,
            'reason' => $export->reason instanceof \BackedEnum ? $export->reason->value : (string) $export->reason,
            'dest_warehouse_id' => $export->dest_warehouse_id,
            'export_date' => $export->export_date->toDateString(),
            'status' => $export->status instanceof \BackedEnum ? $export->status->value : (string) $export->status,
            'total_cost' => (string) $export->total_cost,
            'items' => $export->items->map(fn (ExportItem $item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'line_total' => $item->line_total,
            ])->all(),
        ];
    }
}
