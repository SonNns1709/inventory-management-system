<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Import;
use Illuminate\Contracts\View\View;

/**
 * Phiếu nhập kho — UC03.01, UC03.03, UC03.04 (giao diện web).
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác nằm trong hai Livewire component:
 *   - App\Livewire\Imports\ImportIndex : tra cứu / lọc / sắp xếp / phân trang (UC03.03)
 *   - App\Livewire\Imports\ImportForm  : lập, sửa, xác nhận, hủy phiếu (UC03.01, UC03.04)
 * Cả hai gọi ImportService — đúng khuôn: Livewire action -> Form Request + Policy -> Service -> Eloquent.
 */
class ImportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Import::class);

        return view('imports.index');
    }

    public function create(): View
    {
        $this->authorize('create', Import::class);

        return view('imports.form', ['importId' => null]);
    }

    public function edit(Import $import): View
    {
        $this->authorize('view', $import);

        return view('imports.form', ['importId' => $import->getKey()]);
    }
}
