<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use Illuminate\Contracts\View\View;

/**
 * Kiểm kê và điều chỉnh tồn kho — UC04.03 (giao diện web).
 *
 * Controller chỉ kiểm tra quyền xem rồi dựng trang; toàn bộ thao tác nằm trong ba Livewire component:
 *   - App\Livewire\Adjustments\AdjustmentIndex  : tra cứu phiếu kiểm kê
 *   - App\Livewire\Adjustments\AdjustmentCreate : lập phiếu nháp + snapshot (chọn kho, phạm vi)
 *   - App\Livewire\Adjustments\AdjustmentSheet  : nhập số đếm, làm mới snapshot, xác nhận, hủy
 * Cả ba gọi StockAdjustmentService — đúng khuôn: Livewire action -> Form Request + Policy -> Service -> Eloquent.
 */
class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', StockAdjustment::class);

        return view('adjustments.index');
    }

    public function create(): View
    {
        $this->authorize('create', StockAdjustment::class);

        return view('adjustments.create');
    }

    /** Dùng chung cho nhập số đếm (draft) và xem phiếu đã xác nhận / đã hủy (sheet tự chuyển sang chỉ đọc). */
    public function edit(StockAdjustment $adjustment): View
    {
        $this->authorize('view', $adjustment);

        return view('adjustments.sheet', ['adjustmentId' => $adjustment->getKey()]);
    }
}
