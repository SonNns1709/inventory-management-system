<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;

/**
 * Kho — UC02.03. MÀN HÌNH THAM CHIẾU của giao diện web.
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác (tìm kiếm, sắp xếp, phân trang, thêm / sửa / xóa)
 * nằm trong Livewire component App\Livewire\Warehouses\WarehouseIndex, gọi WarehouseService — đúng khuôn của báo cáo:
 *   Livewire action -> Form Request (luật kiểm tra) + Policy (quyền) -> Service -> Eloquent.
 */
class WarehouseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('warehouses.index');
    }
}
