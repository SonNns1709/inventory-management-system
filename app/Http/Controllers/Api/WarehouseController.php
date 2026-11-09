<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Kho — UC02.03 (bảng API, nhóm 2: #16 – #20). GET /warehouses kèm tổng tồn của từng kho (total_stock). */
class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WarehouseService $warehouses) {}

    /** GET /api/warehouses. Tham số: keyword, is_active, sort, direction, per_page, page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        return $this->successPaginated(WarehouseResource::class, $this->warehouses->list($request->query()));
    }

    /** GET /api/warehouses/{warehouse}. */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return $this->success(WarehouseResource::make($this->warehouses->find($warehouse->getKey())));
    }

    /** POST /api/warehouses — Admin. */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        return $this->created(WarehouseResource::make($this->warehouses->create($request->validated())));
    }

    /** PUT /api/warehouses/{warehouse} — Admin. */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        return $this->success(WarehouseResource::make($this->warehouses->update($warehouse, $request->validated())));
    }

    /** DELETE /api/warehouses/{warehouse} — Admin; còn dữ liệu liên quan thì Service ném 409. */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        $this->warehouses->delete($warehouse);

        return $this->message('Xóa kho thành công');
    }

    /** PATCH /api/warehouses/{warehouse}/toggle (#20) — ngừng hoạt động chỉ khi tổng tồn của kho bằng 0 (BR02.03-3). */
    public function toggle(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        return $this->success(WarehouseResource::make($this->warehouses->toggle($warehouse)));
    }

    /** GET /api/warehouses/{warehouse}/inventories (+) — tồn từng sản phẩm trong kho (sơ đồ lớp: WarehouseController::inventories). */
    public function inventories(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return $this->success(InventoryResource::collection($this->warehouses->inventories($warehouse)));
    }
}
