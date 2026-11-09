<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckExportAvailabilityRequest;
use App\Http\Requests\StoreExportRequest;
use App\Http\Requests\UpdateExportRequest;
use App\Http\Resources\ExportResource;
use App\Models\Export;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phiếu xuất kho (bán hàng hoặc chuyển kho) — UC03.02, UC03.03, UC03.04 (bảng API, nhóm 5: #36 – #42).
 * Khi confirm: kiểm tra lại tồn của TẤT CẢ dòng, hết tồn thì không xác nhận một phần mà trả 409 (BR03.02-4).
 */
class ExportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ExportService $exports) {}

    /** GET /api/exports (#36) — tra cứu phân trang. Tham số: keyword, from, to, status, reason, warehouse_id, sort, direction, per_page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Export::class);

        return $this->successPaginated(ExportResource::class, $this->exports->search($request->query()));
    }

    /** POST /api/exports/check-availability (#37) — { "data": { "is_available": bool, "details": [...] } }. */
    public function checkAvailability(CheckExportAvailabilityRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->success($this->exports->checkAvailability((int) $data['warehouse_id'], $data['items']));
    }

    /** POST /api/exports (#38) — lưu nháp, chưa trừ tồn (BR03.02-8). */
    public function store(StoreExportRequest $request): JsonResponse
    {
        return $this->created(ExportResource::make($this->exports->createDraft($request->user(), $request->validated())));
    }

    /** GET /api/exports/{export} (#39) */
    public function show(Export $export): JsonResponse
    {
        $this->authorize('view', $export);

        return $this->success(ExportResource::make($this->exports->getDetail($export->getKey())));
    }

    /** PUT /api/exports/{export} (#40) — sửa phiếu nháp. */
    public function update(UpdateExportRequest $request, Export $export): JsonResponse
    {
        return $this->success(ExportResource::make($this->exports->updateDraft($export, $request->validated())));
    }

    /** POST /api/exports/{export}/confirm (#41) — trừ tồn kho xuất (cộng kho đích nếu chuyển kho), ghi thẻ kho. */
    public function confirm(Request $request, Export $export): JsonResponse
    {
        $this->authorize('confirm', $export);

        return $this->success(ExportResource::make($this->exports->confirm($export, $request->user())));
    }

    /** POST /api/exports/{export}/cancel (#42) */
    public function cancel(Request $request, Export $export): JsonResponse
    {
        $this->authorize('cancel', $export);

        return $this->success(ExportResource::make($this->exports->cancel($export, $request->user())));
    }
}
