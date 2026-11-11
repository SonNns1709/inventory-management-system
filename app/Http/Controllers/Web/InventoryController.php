<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Quản lý tồn kho — UC04.01. MÀN HÌNH THAM CHIẾU của giao diện web.
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác (tìm kiếm, sắp xếp, phân trang, thêm / sửa / xóa)
 * nằm trong Livewire component App\Livewire\Inventories\InventoryIndex, gọi InventoryService — đúng khuôn của báo cáo:
 *   Livewire action -> Form Request (luật kiểm tra) + Policy (quyền) -> Service -> Eloquent.
 */
class InventoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('inventories.index');
    }

    public function stockCard(Request $request, Product $product, InventoryService $inventory): View
    {
        $this->authorize('view', $product);

        $filters = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $product->loadSum('inventories', 'quantity');

        return view('inventories.stock-card', [
            'product' => $product,
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name']),
            'warehouseId' => $filters['warehouse_id'] ?? '',
            'from' => $filters['from'] ?? '',
            'to' => $filters['to'] ?? '',
            'card' => $inventory->getStockCard(
                $product,
                isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null,
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            ),
        ]);
    }
}
