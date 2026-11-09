<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Danh mục sản phẩm — UC02.01 (bảng API, nhóm 2: #7 – #10). MẪU THAM CHIẾU cho các Controller còn lại:
 *
 *   Request -> Form Request (validate + authorize bằng Policy) -> Service (nghiệp vụ) -> Resource -> { "data": ... }
 *
 * Controller không có try/catch: lỗi nghiệp vụ là BusinessConflictException (409) do Service ném ra,
 * bootstrap/app.php đổi thành JSON; không tìm thấy bản ghi (route model binding) là 404.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryService $categories) {}

    /**
     * GET /api/categories (#7) — mọi tài khoản đã đăng nhập.
     * Tham số: keyword, sort (id|name|created_at), direction (asc|desc), per_page (10|20|50|100), page.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->successPaginated(CategoryResource::class, $this->categories->list($request->query()));
    }

    /** GET /api/categories/{category} (+) — kèm số sản phẩm của danh mục (products_count). */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return $this->success(CategoryResource::make($this->categories->find($category->getKey())));
    }

    /** POST /api/categories (#8) — Admin. Dữ liệu và quyền do StoreCategoryRequest kiểm tra (403 / 422). */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->created(CategoryResource::make($this->categories->create($request->validated())));
    }

    /** PUT /api/categories/{category} (#9) — Admin. */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        return $this->success(CategoryResource::make($this->categories->update($category, $request->validated())));
    }

    /** DELETE /api/categories/{category} (#10) — Admin; còn sản phẩm thì Service ném 409 (BR02.01-3). */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return $this->message('Xóa danh mục thành công');
    }
}
