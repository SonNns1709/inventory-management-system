<?php

namespace App\Livewire\Inventories;

use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC04.01 — Tồn kho theo sản phẩm / kho (chỉ xem). Khuôn giống CategoryIndex nhưng không có thêm / sửa / xóa:
 *   Livewire -> InventoryService::getStock() -> InventoryFilter (whitelist) -> Eloquent.
 */
class InventoryIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(as: 'product', except: '')]
    public string $productId = '';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseId(): void
    {
        $this->resetPage();
    }

    public function updatedProductId(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $service = app(InventoryService::class);

        $params = [
            'keyword' => $this->keyword,
            'per_page' => $this->perPage,
        ];

        if ($this->warehouseId !== '') {
            $params['warehouse_id'] = (int) $this->warehouseId;
        }

        if ($this->productId !== '') {
            $params['product_id'] = (int) $this->productId;
        }

        return view('livewire.inventories.inventory-index', [
            'inventories' => $service->getStock($params),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name']),
            'totalValue' => $service->totalValue(),
        ]);
    }
}