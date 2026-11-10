<?php

namespace App\Livewire\Products;

use App\Exceptions\BusinessConflictException;
use App\Http\Filters\ProductFilter;
use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** UC02.04 – UC02.07 — product search and Admin management. */
class ProductIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(except: '')]
    public string $categoryId = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: false)]
    public bool $lowStock = false;

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public int $perPage = 20;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $sku = '';

    public string $name = '';

    public string $category_id = '';

    public string $unit = '';

    public ?string $price = '0';

    public string $min_stock_level = '0';

    public string $description = '';

    public ?int $deletingId = null;

    public string $deletingName = '';

    public ?int $togglingId = null;

    public string $togglingName = '';

    public bool $toggleWillDeactivate = false;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedLowStock(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ProductFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Product::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $this->authorize('update', $product);

        $this->resetForm();
        $this->editingId = $product->id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->category_id = (string) $product->category_id;
        $this->unit = $product->unit;
        $this->price = (string) $product->price;
        $this->min_stock_level = (string) $product->min_stock_level;
        $this->description = (string) $product->description;
        $this->showForm = true;
    }

    public function save(): mixed
    {
        $product = $this->editingId !== null ? Product::query()->findOrFail($this->editingId) : null;

        if ($product === null) {
            $this->authorize('create', Product::class);
        } else {
            $this->authorize('update', $product);
        }

        $this->sku = strtoupper(trim($this->sku));
        $this->name = trim($this->name);
        $this->unit = trim($this->unit);
        $this->description = trim($this->description);
        $this->price = trim((string) $this->price) === '' ? '0' : trim((string) $this->price);

        $request = new StoreProductRequest;
        $data = $this->validate(
            StoreProductRequest::rulesFor($product),
            $request->messages(),
            $request->attributes(),
        );
        $data['description'] = ($data['description'] ?? '') !== '' ? $data['description'] : null;

        $service = app(ProductService::class);

        if ($product === null) {
            $product = $service->create($data);
            $this->flash('Đã thêm sản phẩm.');
            $this->closeForm();

            return $this->redirectRoute('products.show', ['product' => $product->id]);
        }

        $product = $service->update($product, $data);
        $this->flash('Đã cập nhật sản phẩm.');
        $this->closeForm();

        return $this->redirectRoute('products.show', ['product' => $product->id]);
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function confirmToggle(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $this->authorize('update', $product);

        $this->togglingId = $product->id;
        $this->togglingName = $product->name;
        $this->toggleWillDeactivate = $product->is_active;
    }

    public function toggle(): void
    {
        abort_if($this->togglingId === null, 404);

        $product = Product::query()->findOrFail($this->togglingId);
        $this->authorize('update', $product);

        $service = app(ProductService::class);
        $product->is_active ? $service->deactivate($product) : $service->activate($product);
        $this->flash($product->is_active ? 'Đã kích hoạt kinh doanh sản phẩm.' : 'Đã ngừng kinh doanh sản phẩm.');
        $this->cancelToggle();
    }

    public function cancelToggle(): void
    {
        $this->togglingId = null;
        $this->togglingName = '';
        $this->toggleWillDeactivate = false;
    }

    public function confirmDelete(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $this->authorize('delete', $product);

        $this->deletingId = $product->id;
        $this->deletingName = $product->name;
    }

    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);

        $product = Product::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $product);

        try {
            app(ProductService::class)->delete($product);
            $this->flash('Đã xóa sản phẩm.');
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
        $params = [
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ];

        if ($this->categoryId !== '') {
            $params['category_id'] = $this->categoryId;
        }
        if ($this->status === 'active') {
            $params['is_active'] = true;
        } elseif ($this->status === 'inactive') {
            $params['is_active'] = false;
        }
        if ($this->lowStock) {
            $params['low_stock'] = true;
        }

        return view('livewire.products.product-index', [
            'products' => app(ProductService::class)->search($params),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canManage' => auth()->user()->can('create', Product::class),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'sku', 'name', 'category_id', 'unit', 'description']);
        $this->price = '0';
        $this->min_stock_level = '0';
        $this->resetValidation();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
