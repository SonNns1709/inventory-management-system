<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\WarehouseFilter;
use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * UC02.03 — Quản lý kho. BR02.03-1 mã kho duy nhất, BR02.03-3 chỉ ngừng hoạt động khi tổng tồn = 0,
 * BR02.03-4 chỉ xóa khi chưa từng phát sinh tồn kho hoặc chứng từ, BR02.03-6 ghi Activity Log.
 * Danh sách và chi tiết kèm total_stock (tổng tồn của kho) — bảng API #16.
 */
class WarehouseService
{
    private const FIELDS = ['code', 'name', 'address'];

    public function __construct(private readonly ActivityLogService $logs) {}

    /** @param  array<string, mixed>  $params  keyword, is_active, sort, direction, per_page */
    public function list(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = WarehouseFilter::valid($params);

        return WarehouseFilter::apply(Warehouse::query()->withSum('inventories as total_stock', 'quantity'), $filters)
            ->paginate($perPage ?? WarehouseFilter::perPage($filters));
    }

    public function find(int|string $id): Warehouse
    {
        return Warehouse::query()->withSum('inventories as total_stock', 'quantity')->findOrFail($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Warehouse
    {
        $warehouse = Warehouse::create($this->payload($data) + ['is_active' => true]);

        $this->logs->record(
            ActivityAction::WarehouseCreated,
            $warehouse,
            "Thêm kho {$warehouse->code} — {$warehouse->name}",
            ['new' => $warehouse->only(self::FIELDS)],
        );

        return $warehouse;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $old = $warehouse->only(self::FIELDS);

        $warehouse->fill($this->payload($data))->save();

        $this->logs->record(
            ActivityAction::WarehouseUpdated,
            $warehouse,
            "Cập nhật kho {$warehouse->code}",
            ['old' => $old, 'new' => $warehouse->only(self::FIELDS)],
        );

        return $warehouse;
    }

    /** BR02.03-4 / BR02.03-5: đã từng có tồn hoặc chứng từ thì không xóa -> HTTP 409 (khóa ngoại RESTRICT là lớp bảo vệ thứ hai). */
    public function delete(Warehouse $warehouse): void
    {
        $used = $warehouse->inventories()->exists()
            || $warehouse->imports()->exists()
            || $warehouse->exports()->exists()
            || $warehouse->incomingTransfers()->exists()
            || $warehouse->stockAdjustments()->exists()
            || $warehouse->stockMovements()->exists();

        if ($used) {
            throw new BusinessConflictException('Kho đã phát sinh tồn kho hoặc chứng từ, không thể xóa — chỉ có thể ngừng hoạt động khi tồn bằng 0');
        }

        $old = $warehouse->only(self::FIELDS);
        $warehouse->delete();

        $this->logs->record(ActivityAction::WarehouseDeleted, $warehouse, "Xóa kho {$old['code']}", ['old' => $old]);
    }

    /** Đổi trạng thái hoạt động của kho (đang hoạt động <-> ngừng). */
    public function toggle(Warehouse $warehouse): Warehouse
    {
        return $warehouse->is_active ? $this->deactivate($warehouse) : $this->activate($warehouse);
    }

    /** BR02.03-3: chỉ ngừng hoạt động khi tổng tồn của kho bằng 0 -> HTTP 409. */
    public function deactivate(Warehouse $warehouse): Warehouse
    {
        if ((int) Inventory::query()->where('warehouse_id', $warehouse->getKey())->sum('quantity') > 0) {
            throw new BusinessConflictException('Kho còn hàng tồn, hãy chuyển hết hàng sang kho khác trước khi ngừng hoạt động');
        }

        $warehouse->forceFill(['is_active' => false])->save();

        $this->logs->record(ActivityAction::WarehouseDeactivated, $warehouse, "Ngừng hoạt động kho {$warehouse->code}");

        return $warehouse;
    }

    public function activate(Warehouse $warehouse): Warehouse
    {
        $warehouse->forceFill(['is_active' => true])->save();

        $this->logs->record(ActivityAction::WarehouseActivated, $warehouse, "Kích hoạt lại kho {$warehouse->code}");

        return $warehouse;
    }

    /**
     * Tồn từng sản phẩm trong kho (sơ đồ lớp: WarehouseController::inventories).
     *
     * @return Collection<int, Inventory>
     */
    public function inventories(Warehouse $warehouse): Collection
    {
        return $warehouse->inventories()->with('product')->orderBy('product_id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return array_merge(array_fill_keys(self::FIELDS, null), array_intersect_key($data, array_flip(self::FIELDS)));
    }
}
