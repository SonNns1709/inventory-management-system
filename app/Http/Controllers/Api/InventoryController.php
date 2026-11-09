<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\ProductResource;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Tồn kho — UC04.01, UC04.04 (bảng API, nhóm 6: #43 – #45). Cả hai vai trò; chỉ đọc. */
class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly InventoryService $inventory) {}

    /** GET /api/inventory (#43) — tồn theo sản phẩm / kho. Tham số: product_id, warehouse_id, keyword, sort, direction, per_page. */
    public function index(Request $request): JsonResponse
    {
        return $this->successPaginated(InventoryResource::class, $this->inventory->getStock($request->query()));
    }

    /**
     * GET /api/inventory/low-stock (#44) — sản phẩm có TỔNG tồn (mọi kho) < min_stock_level,
     * sắp xếp giảm dần theo mức thiếu (BR04.04-3 – BR04.04-6).
     */
    public function lowStock(): JsonResponse
    {
        return $this->success(ProductResource::collection($this->inventory->getLowStock()));
    }

    /** GET /api/inventory/total-value (#45) — Σ (số lượng tồn × giá vốn bình quân) (BR05.01-3). */
    public function totalValue(): JsonResponse
    {
        return $this->success(['inventory_value' => $this->inventory->totalValue()]);
    }
}
