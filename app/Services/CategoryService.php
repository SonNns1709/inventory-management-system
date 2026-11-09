<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\CategoryFilter;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * UC02.01 — Quản lý danh mục sản phẩm. ĐÂY LÀ LÁT CẮT THAM CHIẾU ĐẦY ĐỦ của dự án:
 * mọi module còn lại (Supplier, Warehouse, Product...) nên viết theo đúng khuôn này.
 *
 *   Route -> Controller (Api\CategoryController | Livewire\Categories\CategoryIndex)
 *         -> Form Request (validate) + Policy (authorize)
 *         -> CategoryService (quy tắc nghiệp vụ + ghi Activity Log)
 *         -> Eloquent Model -> MySQL
 *
 * Quy tắc: BR02.01-1 tên không trống, BR02.01-2 tên duy nhất (Form Request + UNIQUE trong CSDL),
 * BR02.01-3 không xóa khi còn sản phẩm, BR02.01-4 chỉ Admin (Policy), BR02.01-5 ghi Activity Log.
 */
class CategoryService
{
    public function __construct(private readonly ActivityLogService $logs) {}

    /**
     * Danh sách phân trang kèm số sản phẩm của từng danh mục (cột products_count).
     *
     * @param  array<string, mixed>  $params  tham số lọc thô (keyword, sort, direction, per_page)
     */
    public function list(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = CategoryFilter::valid($params);

        return CategoryFilter::apply(Category::query()->withCount('products'), $filters)
            ->paginate($perPage ?? CategoryFilter::perPage($filters));
    }

    /**
     * Toàn bộ danh mục theo A–Z, dùng cho ô chọn "Danh mục" ở form sản phẩm.
     *
     * @return Collection<int, Category>
     */
    public function all(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /** Một danh mục kèm số sản phẩm (404 nếu không có). */
    public function find(int|string $id): Category
    {
        return Category::query()->withCount('products')->findOrFail($id);
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function create(array $data): Category
    {
        $category = Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->logs->record(
            ActivityAction::CategoryCreated,
            $category,
            "Thêm danh mục \"{$category->name}\"",
            ['new' => $category->only(['name', 'description'])],
        );

        return $category;
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $old = $category->only(['name', 'description']);

        $category->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ])->save();

        $this->logs->record(
            ActivityAction::CategoryUpdated,
            $category,
            "Cập nhật danh mục \"{$category->name}\"",
            ['old' => $old, 'new' => $category->only(['name', 'description'])],
        );

        return $category;
    }

    /**
     * BR02.01-3 / UC02.01 E2: không xóa danh mục còn sản phẩm tham chiếu -> HTTP 409.
     * (Khóa ngoại fk_products_category ON DELETE RESTRICT là lớp bảo vệ thứ hai ở tầng CSDL.)
     *
     * @throws BusinessConflictException
     */
    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new BusinessConflictException('Danh mục còn sản phẩm, không thể xóa');
        }

        $old = $category->only(['name', 'description']);
        $category->delete();

        $this->logs->record(
            ActivityAction::CategoryDeleted,
            $category,
            "Xóa danh mục \"{$old['name']}\"",
            ['old' => $old],
        );
    }
}
