<?php

namespace App\Livewire\Inventory;

use App\Http\Filters\InventoryFilter;
use App\Models\Category;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC04.01 — Tồn kho theo sản phẩm và kho (view: resources/views/livewire/inventory/inventory-index.blade.php).
 *
 * Chỉ đọc (BR04.01-3): dữ liệu lấy từ InventoryService::getStock(), component không ghi gì vào CSDL.
 * Thuộc tính public do trình duyệt gửi lên nên không tin được: sort / direction / per_page / các id đều đi qua
 * InventoryFilter::valid() (whitelist + kiểm tra kiểu), giá trị lạ bị bỏ qua chứ không gây lỗi 500.
 */
class InventoryIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    // Id kho / danh mục giữ dạng chuỗi vì thẻ <select> luôn gửi chuỗi; '' nghĩa là "Tất cả".
    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'low', except: false)]
    public bool $lowOnly = false;

    #[Url(except: 'product_id')]
    public string $sort = 'product_id';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    /** Đổi bộ lọc thì quay về trang 1 (tránh đang ở một trang không còn dữ liệu). */
    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'warehouseId', 'categoryId', 'lowOnly', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    /** Bấm tiêu đề cột: đổi cột sắp xếp, hoặc đảo chiều nếu bấm lại cùng cột (cột phải nằm trong InventoryFilter::SORTABLE). */
    public function sortBy(string $column): void
    {
        if (! in_array($column, InventoryFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'warehouseId', 'categoryId', 'lowOnly']);
        $this->resetPage();
    }

    public function render(): View
    {
        // Chuỗi rỗng phải đổi thành null: InventoryFilter coi "có khóa và khác null" là đang lọc.
        $rows = app(InventoryService::class)->getStock([
            'keyword' => $this->keyword,
            'warehouse_id' => $this->warehouseId !== '' ? $this->warehouseId : null,
            'category_id' => $this->categoryId !== '' ? $this->categoryId : null,
            'low_stock' => $this->lowOnly ? 1 : null,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.inventory.inventory-index', [
            'rows' => $rows,
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
