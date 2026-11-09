<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CostingService;
use App\Services\DocumentNumberService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tồn đầu kỳ mẫu — mỗi kho một PHIẾU NHẬP ĐÃ XÁC NHẬN (ghi chú "Tồn đầu kỳ (dữ liệu mẫu)").
 *
 * Nạp tồn bằng phiếu nhập + InventoryService::applyMovement() thay vì ghi thẳng vào `inventories`, để dữ liệu mẫu nhất quán
 * với nghiệp vụ thật: mỗi dòng tồn đều có một dòng thẻ kho (stock_movements) và một chứng từ gốc (imports) — thẻ kho của sản phẩm
 * (GET /api/products/{id}/stock-card) và Dashboard cho kết quả đúng ngay từ đầu. Đơn giá nhập = giá vốn của sản phẩm nên WAC không đổi.
 *
 * Số liệu được chọn để có 4 sản phẩm dưới ngưỡng (cảnh báo tồn thấp, BR04.04):
 *   VP-0001 (30 < 50, thiếu 20), DT-0003 (12 < 30, thiếu 18), DT-0004 (5 < 8, thiếu 3), TP-0002 (9 < 12, thiếu 3).
 * Chạy lại seeder sẽ bỏ qua nếu tồn đầu kỳ đã được nạp.
 */
class InventorySeeder extends Seeder
{
    public const NOTE = 'Tồn đầu kỳ (dữ liệu mẫu)';

    /** @var array<string, array<string, int>> mã kho => [SKU => số lượng] */
    private const OPENING_STOCK = [
        'KHO-HN' => [
            'DT-0001' => 25, 'DT-0002' => 8, 'DT-0003' => 12, 'DT-0004' => 3,
            'GD-0001' => 15, 'GD-0002' => 6, 'VP-0001' => 20, 'VP-0002' => 40,
            'VP-0003' => 60, 'TP-0001' => 30, 'TP-0002' => 9, 'DC-0001' => 14,
        ],
        'KHO-HCM' => [
            'DT-0001' => 10, 'DT-0002' => 4, 'DT-0004' => 2,
            'GD-0001' => 9, 'VP-0001' => 10, 'VP-0002' => 25,
            'TP-0001' => 18, 'DC-0001' => 6,
        ],
    ];

    public function run(DocumentNumberService $numbers, InventoryService $inventory, CostingService $costing): void
    {
        if (Import::query()->where('note', self::NOTE)->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@inventory.test')->firstOrFail();
        $supplier = Supplier::query()->where('is_active', true)->orderBy('id')->firstOrFail();
        $products = Product::query()->get()->keyBy('sku');

        foreach (self::OPENING_STOCK as $warehouseCode => $lines) {
            $warehouse = Warehouse::query()->where('code', $warehouseCode)->firstOrFail();

            // Một transaction cho mỗi phiếu: lỗi ở dòng nào thì hủy cả phiếu, không ghi sổ dở dang
            DB::transaction(function () use ($numbers, $inventory, $costing, $admin, $supplier, $products, $warehouse, $lines) {
                $import = Import::create([
                    'code' => $numbers->next('import', now()),
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'import_date' => now()->toDateString(),
                    'status' => DocumentStatus::Confirmed,
                    'total_amount' => 0,
                    'note' => self::NOTE,
                    'created_by' => $admin->id,
                    'confirmed_by' => $admin->id,
                    'confirmed_at' => now(),
                ]);

                $total = 0.0;

                foreach ($lines as $sku => $quantity) {
                    $product = $products[$sku];
                    $unitCost = $costing->cost($product->avg_cost);
                    $lineTotal = (float) $unitCost * $quantity;

                    ImportItem::create([
                        'import_id' => $import->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'line_total' => number_format($lineTotal, 2, '.', ''),
                    ]);

                    $inventory->applyMovement(
                        $product,
                        $warehouse,
                        MovementType::Import,
                        $quantity,
                        'imports',
                        $import->id,
                        $unitCost,
                        $unitCost,
                        $admin,
                    );

                    $total += $lineTotal;
                }

                $import->update(['total_amount' => number_format($total, 2, '.', '')]);
            });
        }
    }
}
