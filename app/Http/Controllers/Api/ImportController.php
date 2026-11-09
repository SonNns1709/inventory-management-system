<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewImportCostRequest;
use App\Http\Requests\StoreImportRequest;
use App\Http\Requests\UpdateImportRequest;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use App\Services\ImportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phiếu nhập kho — UC03.01, UC03.03, UC03.04 (bảng API, nhóm 4: #29 – #35).
 * Vòng đời: draft -> confirmed | cancelled. Chỉ khi confirm mới cộng tồn, tính lại giá vốn bình quân và ghi thẻ kho.
 */
class ImportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ImportService $imports) {}

    /** GET /api/imports (#29) — tra cứu phân trang. Tham số: keyword, from, to, status, supplier_id, warehouse_id, sort, direction, per_page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Import::class);

        return $this->successPaginated(ImportResource::class, $this->imports->search($request->query()));
    }

    /** POST /api/imports/preview-cost (#30) — { "data": { "newAvgCost": ... } }, không ghi gì vào CSDL. */
    public function previewCost(PreviewImportCostRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->success([
            'newAvgCost' => $this->imports->previewCost((int) $data['product_id'], (int) $data['qtyIn'], (string) $data['unitCost']),
        ]);
    }

    /** POST /api/imports (#31) — lưu nháp, chưa thay đổi tồn kho (BR03.01-3). */
    public function store(StoreImportRequest $request): JsonResponse
    {
        return $this->created(ImportResource::make($this->imports->createDraft($request->user(), $request->validated())));
    }

    /** GET /api/imports/{import} (#32) — chi tiết kèm dòng hàng, người lập, người xác nhận (BR03.03-6). */
    public function show(Import $import): JsonResponse
    {
        $this->authorize('view', $import);

        return $this->success(ImportResource::make($this->imports->getDetail($import->getKey())));
    }

    /** PUT /api/imports/{import} (#33) — sửa phiếu nháp; phiếu đã xác nhận / hủy thì 409. */
    public function update(UpdateImportRequest $request, Import $import): JsonResponse
    {
        return $this->success(ImportResource::make($this->imports->updateDraft($import, $request->validated())));
    }

    /** POST /api/imports/{import}/confirm (#34) — trong một transaction: khóa dòng tồn, cộng tồn, tính lại giá vốn, ghi thẻ kho. */
    public function confirm(Request $request, Import $import): JsonResponse
    {
        $this->authorize('confirm', $import);

        return $this->success(ImportResource::make($this->imports->confirm($import, $request->user())));
    }

    /** POST /api/imports/{import}/cancel (#35) — chỉ phiếu draft; người lập hoặc Admin (BR03.04-5). */
    public function cancel(Request $request, Import $import): JsonResponse
    {
        $this->authorize('cancel', $import);

        return $this->success(ImportResource::make($this->imports->cancel($import, $request->user())));
    }
}
