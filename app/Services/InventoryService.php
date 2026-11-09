<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\InventoryFilter;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Tồn kho và thẻ kho — UC04.01, UC04.02, UC04.04 (báo cáo, Sơ đồ lớp thiết kế (Sơ đồ 73): InventoryService).
 *
 *  - getStock / getLowStock / getStockCard / totalValue: tra cứu, KHÔNG làm thay đổi dữ liệu (BR04.01-3, BR04.02-8, BR04.04-7);
 *  - applyMovement: nguyên tử DUY NHẤT làm thay đổi tồn kho. Mọi nghiệp vụ ghi sổ (xác nhận phiếu nhập / xuất / kiểm kê)
 *    phải đi qua hàm này để mỗi dòng giao dịch sinh đúng một dòng stock_movements (BR04.02-4).
 */
class InventoryService
{
    public function __construct(private readonly CostingService $costing) {}

    /**
     * UC04.01 — tồn theo sản phẩm / kho, phân trang. BR04.01-2 / BR04.01-4: số lượng đọc trực tiếp từ bảng `inventories`
     * (mỗi dòng đúng một cặp sản phẩm + kho); quyền xem (BR04.01-5) do InventoryController / route kiểm tra.
     *
     * @param  array<string, mixed>  $params  product_id, warehouse_id, keyword, sort, direction, per_page
     */
    public function getStock(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = InventoryFilter::valid($params);

        return InventoryFilter::apply(Inventory::query()->with(['product', 'warehouse']), $filters)
            ->paginate($perPage ?? InventoryFilter::perPage($filters));
    }

    /**
     * UC04.04 — sản phẩm đang kinh doanh có TỔNG tồn (mọi kho) < min_stock_level, mức thiếu giảm dần (BR04.04-3 – BR04.04-6).
     * BR04.04-1: cả Admin và Nhân viên kho đều xem được; BR04.04-2: ngưỡng là `products.min_stock_level` của TỪNG sản phẩm;
     * BR04.04-8: mỗi dòng có `id` sản phẩm để màn hình chuyển sang chi tiết và thẻ kho.
     *
     * @return Collection<int, Product>
     */
    public function getLowStock(?int $limit = null): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->withTotalStock()
            ->lowStock()
            ->orderByShortage()
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
    }

    /**
     * UC04.02 — thẻ kho của một sản phẩm (tùy chọn theo kho và khoảng ngày; BR04.02-2).
     * BR04.02-1: Admin và Nhân viên kho đều xem được. BR04.02-3: tồn đầu kỳ = tổng qty_change của các biến động TRƯỚC ngày bắt đầu.
     * BR04.02-5: sổ chỉ ghi thêm (model StockMovement cấm sửa / xóa). BR04.02-6: mỗi dòng có ngày (created_at), loại (type),
     * số phiếu (reference_code), số lượng thay đổi (qty_change), tồn sau (balance_after) và giá vốn (unit_cost, avg_cost_after).
     * BR04.02-7: (reference_type, reference_id) cho phép mở phiếu gốc.
     *
     * @return array{opening_balance: int, closing_balance: int, avg_cost: string, movements: Collection<int, StockMovement>}
     */
    public function getStockCard(Product $product, ?int $warehouseId = null, ?string $from = null, ?string $to = null): array
    {
        $base = StockMovement::query()
            ->where('product_id', $product->getKey())
            ->when($warehouseId !== null, fn ($query) => $query->where('warehouse_id', $warehouseId));

        $opening = $from === null
            ? 0
            : (int) (clone $base)->where('created_at', '<', Carbon::parse($from)->startOfDay())->sum('qty_change');

        $movements = (clone $base)
            ->when($from !== null, fn ($query) => $query->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($to !== null, fn ($query) => $query->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->with(['warehouse', 'creator'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->attachReferenceCodes($movements);

        return [
            'opening_balance' => $opening,
            'closing_balance' => $opening + (int) $movements->sum('qty_change'),
            'avg_cost' => (string) $product->avg_cost,
            'movements' => $movements,
        ];
    }

    /**
     * BR04.02-6 / BR04.02-7: sổ thẻ kho chỉ lưu (reference_type, reference_id) nên số phiếu (cột `code` của chứng từ gốc)
     * được tra thêm bằng MỘT truy vấn cho mỗi loại chứng từ rồi gắn vào thuộc tính `reference_code` của từng dòng
     * (chứng từ không còn tồn tại -> null, không báo lỗi).
     *
     * @param  Collection<int, StockMovement>  $movements
     */
    private function attachReferenceCodes(Collection $movements): void
    {
        foreach ($movements->groupBy('reference_type') as $table => $rows) {
            if (! in_array($table, ['imports', 'exports', 'stock_adjustments'], true)) {
                continue;
            }

            $codes = DB::table($table)->whereIn('id', $rows->pluck('reference_id')->unique()->all())->pluck('code', 'id');

            foreach ($rows as $movement) {
                $movement->setAttribute('reference_code', $codes->get($movement->reference_id));
            }
        }
    }

    /** UC04.01 / BR05.01-3 — tổng giá trị tồn kho (chuỗi 2 chữ số thập phân). */
    public function totalValue(): string
    {
        return $this->costing->inventoryValue();
    }

    /**
     * Áp một biến động vào tồn kho: cộng / trừ inventories.quantity (khóa dòng, tạo dòng mới nếu chưa có) rồi ghi MỘT dòng
     * stock_movements (sổ chỉ ghi thêm). PHẢI gọi trong DB::transaction() của nghiệp vụ gọi nó, để lỗi ở bất kỳ dòng hàng nào
     * cũng làm hủy cả phiếu — không bao giờ ghi sổ một phần (BR03.01-8, BR03.02-4).
     *
     * @param  int  $qtyChange  dương = nhập thêm, âm = xuất bớt
     * @param  string  $referenceType  bảng chứng từ gốc: imports | exports | stock_adjustments
     * @param  string|int|float  $unitCost  nhập: đơn giá nhập; xuất / chuyển kho / kiểm kê: giá vốn bình quân tại thời điểm xác nhận
     * @param  string|int|float  $avgCostAfter  giá vốn bình quân của sản phẩm SAU biến động
     *
     * @throws BusinessConflictException  tồn sau biến động sẽ âm (hết hàng) -> HTTP 409
     */
    public function applyMovement(
        Product|int $product,
        Warehouse|int $warehouse,
        MovementType $type,
        int $qtyChange,
        string $referenceType,
        int $referenceId,
        string|int|float $unitCost,
        string|int|float $avgCostAfter,
        User|int $actor,
    ): StockMovement {
        $productId = $product instanceof Product ? $product->getKey() : $product;
        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->getKey() : $warehouse;

        $row = Inventory::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first()
            ?? Inventory::create(['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => 0]);

        $balance = (int) $row->quantity + $qtyChange;

        if ($balance < 0) {
            throw new BusinessConflictException('Tồn kho không đủ để thực hiện giao dịch (sản phẩm #'.$productId.', kho #'.$warehouseId.')');
        }

        $row->quantity = $balance;
        $row->save();

        return StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'qty_change' => $qtyChange,
            'balance_after' => $balance,
            'unit_cost' => $this->costing->cost($unitCost),
            'avg_cost_after' => $this->costing->cost($avgCostAfter),
            'created_by' => $actor instanceof User ? $actor->getKey() : $actor,
        ]);
    }
}
