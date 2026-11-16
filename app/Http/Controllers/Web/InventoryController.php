<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;

/**
 * Tồn kho (UC04.01) và Thẻ kho (UC04.02) — giao diện web.
 *
 * Cả Admin và Nhân viên kho đều xem được (BR04.01-5, BR04.02-1) nên chỉ cần middleware auth + active + password.changed
 * của nhóm route trong routes/web.php, không cần Policy riêng. Toàn bộ thao tác lọc / sắp xếp nằm trong hai Livewire component
 * App\Livewire\Inventory\InventoryIndex và StockCard, đều gọi App\Services\InventoryService (chỉ đọc — BR04.01-3, BR04.02-5).
 */
class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.index');
    }

    public function stockCard(Product $product): View
    {
        return view('inventory.stock-card', ['product' => $product]);
    }

    public function lowStock(InventoryService $inventory): View
    {
        return view('inventory.low-stock', ['items' => $inventory->getLowStock()]);
    }
}
