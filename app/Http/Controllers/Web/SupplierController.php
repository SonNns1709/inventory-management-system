<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;

/**
 * Danh mục nhà cung cấp — UC02.02. MÀN HÌNH THAM CHIẾU của giao diện web.
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác (tìm kiếm, sắp xếp, phân trang, thêm / sửa / xóa)
 * nằm trong Livewire component App\Livewire\Suppliers\SupplierIndex, gọi SupplierService — đúng khuôn của báo cáo:
 *   Livewire action -> Form Request (luật kiểm tra) + Policy (quyền) -> Service -> Eloquent.
 */
class SupplierController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Supplier::class);

        return view('suppliers.index');
    }
}
