<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use DateTime;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * UC04.02 — Thẻ kho của một sản phẩm (view: resources/views/livewire/inventory/stock-card.blade.php).
 *
 * Sổ chỉ ghi thêm (BR04.02-5) nên component chỉ đọc qua InventoryService::getStockCard(): tồn đầu kỳ (BR04.02-3) + các dòng
 * biến động theo thời gian, mỗi dòng có ngày, số phiếu, loại, số lượng thay đổi, tồn sau và giá vốn (BR04.02-6).
 */
class StockCard extends Component
{
    // #[Locked]: trình duyệt không được đổi id sản phẩm sau khi trang đã dựng.
    #[Locked]
    public int $productId;

    // Bộ lọc nhập trên form và chỉ có hiệu lực khi bấm "Xem" (wire:model không có .live nên chỉ gửi kèm hành động apply).
    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
    }

    /** Nút "Xem": kiểm tra ngày nhập vào; Livewire vẽ lại bảng ngay sau đó với bộ lọc mới. */
    public function apply(): void
    {
        $this->validate(
            [
                'from' => 'nullable|date_format:Y-m-d',
                'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            ],
            [],
            ['from' => 'Từ ngày', 'to' => 'Đến ngày'],
        );
    }

    public function render(): View
    {
        $product = Product::query()->with('category')->findOrFail($this->productId);

        // Giá trị lấy từ URL (?from=abc) do người dùng tự gõ nên phải kiểm tra trước khi đưa xuống Service (Carbon::parse sẽ ném lỗi).
        $warehouseId = ctype_digit($this->warehouseId) ? (int) $this->warehouseId : null;
        $fromDate = $this->validDate($this->from);
        $toDate = $this->validDate($this->to);

        $stock = Inventory::query()->where('product_id', $product->id);

        return view('livewire.inventory.stock-card', [
            'product' => $product,
            'card' => app(InventoryService::class)->getStockCard($product, $warehouseId, $fromDate, $toDate),
            'activeWarehouseId' => $warehouseId,
            'fromDate' => $fromDate,
            // Tồn hiện tại của kho đang chọn (hoặc mọi kho) — không phụ thuộc khoảng ngày; totalStock dùng để so với ngưỡng (BR04.04-3)
            'currentStock' => (int) (clone $stock)->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))->sum('quantity'),
            'totalStock' => (int) $stock->sum('quantity'),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Chỉ nhận ngày đúng dạng YYYY-MM-DD (và có thật, ví dụ không nhận 2026-02-31); ngược lại coi như không lọc. */
    private function validDate(string $value): ?string
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
