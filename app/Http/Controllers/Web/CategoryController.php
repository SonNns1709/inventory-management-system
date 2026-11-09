<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;

/**
 * Danh mục sản phẩm — UC02.01. MÀN HÌNH THAM CHIẾU của giao diện web.
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác (tìm kiếm, sắp xếp, phân trang, thêm / sửa / xóa)
 * nằm trong Livewire component App\Livewire\Categories\CategoryIndex, gọi CategoryService — đúng khuôn của báo cáo:
 *   Livewire action -> Form Request (luật kiểm tra) + Policy (quyền) -> Service -> Eloquent.
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('categories.index');
    }
}
