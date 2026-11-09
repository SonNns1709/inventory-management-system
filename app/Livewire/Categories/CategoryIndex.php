<?php

namespace App\Livewire\Categories;

use App\Exceptions\BusinessConflictException;
use App\Http\Filters\CategoryFilter;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC02.01 — Danh mục sản phẩm. MÀN HÌNH LIVEWIRE THAM CHIẾU: các màn hình danh mục còn lại (Nhà cung cấp, Kho, Sản phẩm...)
 * nên copy khuôn này.
 *
 *   thao tác Livewire -> $this->authorize() (Policy)  -> luật kiểm tra dùng chung với API (StoreCategoryRequest::rulesFor)
 *                     -> CategoryService (nghiệp vụ + Activity Log) -> Eloquent
 *
 * Lưu ý bảo mật: thuộc tính public của Livewire do trình duyệt gửi lên nên KHÔNG tin được — mọi action đều nạp lại bản ghi
 * từ CSDL và gọi lại Policy; tham số sắp xếp / số dòng đi qua CategoryFilter (whitelist) nên giá trị lạ bị bỏ qua.
 */
class CategoryIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    // --- Tìm kiếm / sắp xếp / phân trang (đồng bộ lên URL để chia sẻ và tải lại trang đúng trạng thái) ---
    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    // Số dòng mỗi trang chỉ giữ trong trạng thái component (không lên URL): giá trị lạ bị CategoryFilter bỏ qua
    public int $perPage = 20;

    // --- Form thêm / sửa ---
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    // --- Xác nhận xóa ---
    public ?int $deletingId = null;

    public string $deletingName = '';

    // --- Thông báo kết quả thao tác ---
    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** Bấm tiêu đề cột: đổi cột sắp xếp, hoặc đảo chiều nếu bấm lại cùng cột. */
    public function sortBy(string $column): void
    {
        if (! in_array($column, CategoryFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('update', $category);

        $this->resetForm();
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->showForm = true;
    }

    /** Lưu (thêm mới khi editingId rỗng, ngược lại cập nhật). BR02.01-1, BR02.01-2 do StoreCategoryRequest::rulesFor() kiểm tra. */
    public function save(): void
    {
        $category = $this->editingId !== null ? Category::query()->findOrFail($this->editingId) : null;

        if ($category === null) {
            $this->authorize('create', Category::class);
        } else {
            $this->authorize('update', $category);
        }

        $this->name = trim($this->name);
        $this->description = trim($this->description);

        $request = new StoreCategoryRequest;
        $data = $this->validate(
            StoreCategoryRequest::rulesFor($category),
            $request->messages(),
            $request->attributes(),
        );
        $data['description'] = ($data['description'] ?? '') !== '' ? $data['description'] : null;

        $service = app(CategoryService::class);

        if ($category === null) {
            $service->create($data);
            $this->flash('Đã thêm danh mục.');
        } else {
            $service->update($category, $data);
            $this->flash('Đã cập nhật danh mục.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->authorize('delete', $category);

        $this->deletingId = $category->id;
        $this->deletingName = $category->name;
    }

    /** BR02.01-3: danh mục còn sản phẩm thì Service ném BusinessConflictException (409 ở API) -> ở đây hiện thành thông báo lỗi. */
    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);

        $category = Category::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $category);

        try {
            app(CategoryService::class)->delete($category);
            $this->flash('Đã xóa danh mục.');
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }

        $this->cancelDelete();
        $this->resetPage();
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->deletingName = '';
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $categories = app(CategoryService::class)->list([
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.categories.category-index', ['categories' => $categories]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'description']);
        $this->resetValidation();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
