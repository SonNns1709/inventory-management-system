<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Export;
use App\Models\Import;
use App\Models\Product;

/**
 * Dashboard — UC05.01 (báo cáo, Sơ đồ lớp thiết kế (Sơ đồ 74): DashboardService::getSummary). Chỉ tra cứu / thống kê (BR05.01-7).
 *
 * 5 chỉ số (BR05.01-2): Total Products, Low Stock, Today's Import, Today's Export, Inventory Value + danh sách rút gọn Low Stock.
 *
 * Các điểm báo cáo chưa nêu rõ — khung chọn như sau, nhóm có thể đổi:
 *  - Total Products: đếm sản phẩm đang kinh doanh (is_active = 1);
 *  - Today's Import / Export: SỐ PHIẾU đã confirmed có confirmed_at trong ngày hiện tại theo giờ máy chủ (BR05.01-4);
 *    kèm tổng giá trị trong todays_import_amount / todays_export_cost.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR05.01-1: Nhân viên kho và Admin đều xem được (route chỉ yêu cầu đăng nhập).
 *  - BR05.01-5: danh sách Low Stock lấy từ các sản phẩm có tổng tồn < min_stock_level.
 *  - BR05.01-6: số liệu tính trực tiếp từ dữ liệu hiện tại; Dashboard chỉ đọc, không thay đổi dữ liệu nghiệp vụ.
 */
class DashboardService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CostingService $costing,
    ) {}

    /** @return array<string, mixed> */
    public function getSummary(): array
    {
        $today = now()->toDateString();
        $lowStock = $this->inventory->getLowStock();

        $imports = Import::query()->where('status', DocumentStatus::Confirmed->value)->whereDate('confirmed_at', $today);
        $exports = Export::query()->where('status', DocumentStatus::Confirmed->value)->whereDate('confirmed_at', $today);

        return [
            'total_products' => Product::query()->where('is_active', true)->count(),
            'low_stock' => $lowStock->count(),
            'todays_import' => (clone $imports)->count(),
            'todays_export' => (clone $exports)->count(),
            'inventory_value' => $this->costing->inventoryValue(),
            'todays_import_amount' => number_format((float) (clone $imports)->sum('total_amount'), 2, '.', ''),
            'todays_export_cost' => number_format((float) (clone $exports)->sum('total_cost'), 2, '.', ''),
            // UC05.01 A1: danh sách rút gọn (mặc định 5 dòng, xem config/inventory.php)
            'low_stock_items' => $lowStock
                ->take((int) config('inventory.dashboard.low_stock_preview', 5))
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'min_stock_level' => (int) $product->min_stock_level,
                    'total_stock' => (int) $product->total_stock,
                    'shortage' => max(0, (int) $product->min_stock_level - (int) $product->total_stock),
                ])
                ->values()
                ->all(),
        ];
    }
}
