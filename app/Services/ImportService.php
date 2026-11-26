<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\ImportFilter;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($actor, $data): Import {
            $importDate = Carbon::parse($data['import_date']);
            $code = $this->numbers->next('import', $importDate);

            $import = Import::create([
                'code' => $code,
                'supplier_id' => (int) $data['supplier_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'import_date' => $importDate->toDateString(),
                'status' => DocumentStatus::Draft,
                'total_amount' => 0,
                'note' => $data['note'] ?? null,
                'created_by' => $actor->getKey(),
            ]);

            $total = $this->replaceItems($import, $data['items']);
            $import->forceFill(['total_amount' => $total])->save();

            $this->logs->record(
                ActivityAction::ImportCreated,
                $import,
                "Lập phiếu nhập {$import->code}",
                ['new' => $this->importSnapshot($import)],
                $actor,
            );

            return $this->getDetail($import->getKey());
        });
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
        return DB::transaction(function () use ($import, $data): Import {
            $import = Import::query()->whereKey($import->getKey())->lockForUpdate()->firstOrFail();

            if (! $import->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được sửa');
            }

            $old = $this->importSnapshot($import);
            $import->fill([
                'supplier_id' => (int) $data['supplier_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'import_date' => $data['import_date'],
                'note' => $data['note'] ?? null,
            ])->save();

            $total = $this->replaceItems($import, $data['items']);
            $import->forceFill(['total_amount' => $total])->save();

            $this->logs->record(
                ActivityAction::ImportUpdated,
                $import,
                "Cập nhật phiếu nhập {$import->code}",
                ['old' => $old, 'new' => $this->importSnapshot($import)],
            );

            return $this->getDetail($import->getKey());
        });
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
        return DB::transaction(function () use ($import, $actor): Import {
            $import = Import::query()->whereKey($import->getKey())->lockForUpdate()->firstOrFail();

            if (! $import->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được xác nhận');
            }

            $items = $import->items()->orderBy('product_id')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw new BusinessConflictException('Phiếu nhập phải có ít nhất một dòng hàng');
            }

            foreach ($items as $item) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                $oldQty = (int) Inventory::query()->where('product_id', $product->getKey())->sum('quantity');
                $newAvg = $this->costing->weightedAverage(
                    $oldQty,
                    (string) $product->avg_cost,
                    (int) $item->quantity,
                    (string) $item->unit_cost,
                );

                $product->forceFill(['avg_cost' => $newAvg])->save();
                $this->inventory->applyMovement(
                    $product,
                    $import->warehouse_id,
                    MovementType::Import,
                    (int) $item->quantity,
                    'imports',
                    (int) $import->getKey(),
                    (string) $item->unit_cost,
                    $newAvg,
                    $actor,
                );
            }

            $import->forceFill([
                'status' => DocumentStatus::Confirmed,
                'confirmed_by' => $actor->getKey(),
                'confirmed_at' => now(),
            ])->save();

            $this->logs->record(
                ActivityAction::ImportConfirmed,
                $import,
                "Xác nhận phiếu nhập {$import->code}",
                ['new' => $this->importSnapshot($import)],
                $actor,
            );

            return $this->getDetail($import->getKey());
        });
    }

    /**
     * UC03.04 — hủy phiếu nháp (BR03.04-1 … BR03.04-6).
     *
     * TODO (Sprint 3): chỉ phiếu draft mới hủy được (confirmed bất biến -> 409); đặt status = cancelled, KHÔNG đụng tới tồn kho;
     * quyền "người lập hoặc Admin" đã do ImportPolicy::cancel kiểm tra; ghi Activity Log ImportCancelled.
     */
    public function cancel(Import $import, User $actor): Import
    {
        return DB::transaction(function () use ($import, $actor): Import {
            $import = Import::query()->whereKey($import->getKey())->lockForUpdate()->firstOrFail();

            if (! $import->isDraft()) {
                throw new BusinessConflictException('Chỉ phiếu nháp mới được hủy');
            }

            $import->forceFill(['status' => DocumentStatus::Cancelled])->save();

            $this->logs->record(
                ActivityAction::ImportCancelled,
                $import,
                "Hủy phiếu nhập {$import->code}",
                ['new' => $this->importSnapshot($import)],
                $actor,
            );

            return $this->getDetail($import->getKey());
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: string}>  $items
     */
    private function replaceItems(Import $import, array $items): string
    {
        $import->items()->delete();
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unitCost = $this->costing->cost($item['unit_cost']);
            $lineTotal = round($quantity * (float) $unitCost, 2);

            ImportItem::create([
                'import_id' => $import->getKey(),
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => number_format($lineTotal, 2, '.', ''),
            ]);

            $total += $lineTotal;
        }

        return number_format($total, 2, '.', '');
    }

    /** @return array<string, mixed> */
    private function importSnapshot(Import $import): array
    {
        $import->loadMissing(['items']);

        return [
            'id' => $import->getKey(),
            'code' => $import->code,
            'supplier_id' => $import->supplier_id,
            'warehouse_id' => $import->warehouse_id,
            'import_date' => $import->import_date->toDateString(),
            'status' => $import->status instanceof \BackedEnum ? $import->status->value : (string) $import->status,
            'total_amount' => (string) $import->total_amount,
            'note' => $import->note,
            'items' => $import->items->map(fn (ImportItem $item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_cost' => (string) $item->unit_cost,
                'line_total' => (string) $item->line_total,
            ])->all(),
        ];
    }
}
