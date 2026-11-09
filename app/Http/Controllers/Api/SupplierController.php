<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Nhà cung cấp — UC02.02 (bảng API, nhóm 2: #11 – #15). Theo đúng khuôn của CategoryController. */
class SupplierController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SupplierService $suppliers) {}

    /** GET /api/suppliers. Tham số: keyword, is_active, sort, direction, per_page, page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        return $this->successPaginated(SupplierResource::class, $this->suppliers->list($request->query()));
    }

    /** GET /api/suppliers/{supplier}. */
    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return $this->success(SupplierResource::make($this->suppliers->find($supplier->getKey())));
    }

    /** POST /api/suppliers — Admin. */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        return $this->created(SupplierResource::make($this->suppliers->create($request->validated())));
    }

    /** PUT /api/suppliers/{supplier} — Admin. */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        return $this->success(SupplierResource::make($this->suppliers->update($supplier, $request->validated())));
    }

    /** DELETE /api/suppliers/{supplier} — Admin; còn dữ liệu liên quan thì Service ném 409. */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->suppliers->delete($supplier);

        return $this->message('Xóa nhà cung cấp thành công');
    }

    /** PATCH /api/suppliers/{supplier}/toggle (#15) — đổi trạng thái hợp tác is_active, không xóa dữ liệu lịch sử (BR02.02-5). */
    public function toggle(Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        return $this->success(SupplierResource::make($this->suppliers->toggle($supplier)));
    }
}
