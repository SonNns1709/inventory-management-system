<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kiểm kê và điều chỉnh tồn kho — UC04.03 (bảng API, nhóm 6: #46 – #50, cộng route `show` và `cancel` lấy từ sơ đồ lớp).
 * Tham số route là {adjustment} (không phải {stockAdjustment}) để khớp URL /api/adjustments/{id} của báo cáo.
 */
class StockAdjustmentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly StockAdjustmentService $adjustments) {}

    /** GET /api/adjustments (#46) — phân trang. Tham số: keyword, from, to, status, warehouse_id, sort, direction, per_page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockAdjustment::class);

        return $this->successPaginated(StockAdjustmentResource::class, $this->adjustments->search($request->query()));
    }

    /** POST /api/adjustments (#47) — tạo phiếu nháp kèm snapshot system_qty hiện tại (BR04.03-2). */
    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        return $this->created(StockAdjustmentResource::make($this->adjustments->createSheet($request->user(), $request->validated())));
    }

    /** GET /api/adjustments/{adjustment} (+) */
    public function show(StockAdjustment $adjustment): JsonResponse
    {
        $this->authorize('view', $adjustment);

        return $this->success(StockAdjustmentResource::make($this->adjustments->getDetail($adjustment->getKey())));
    }

    /** PUT /api/adjustments/{adjustment} (#48) — lưu số đếm thực tế, tự tính diff_qty = counted_qty − system_qty (BR04.03-4). */
    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $adjustment): JsonResponse
    {
        return $this->success(StockAdjustmentResource::make($this->adjustments->saveCounts($adjustment, $request->validated())));
    }

    /** POST /api/adjustments/{adjustment}/refresh (#49) — lấy lại system_qty mới nhất nếu tồn đã đổi từ lúc lập phiếu. */
    public function refresh(StockAdjustment $adjustment): JsonResponse
    {
        $this->authorize('update', $adjustment);

        return $this->success(StockAdjustmentResource::make($this->adjustments->refresh($adjustment)));
    }

    /** POST /api/adjustments/{adjustment}/confirm (#50) — điều chỉnh tồn thực tế, ghi chênh lệch + lý do vào thẻ kho. */
    public function confirm(Request $request, StockAdjustment $adjustment): JsonResponse
    {
        $this->authorize('confirm', $adjustment);

        return $this->success(StockAdjustmentResource::make($this->adjustments->confirm($adjustment, $request->user())));
    }

    /** POST /api/adjustments/{adjustment}/cancel (+) — hủy phiếu nháp. */
    public function cancel(Request $request, StockAdjustment $adjustment): JsonResponse
    {
        $this->authorize('cancel', $adjustment);

        return $this->success(StockAdjustmentResource::make($this->adjustments->cancel($adjustment, $request->user())));
    }
}
