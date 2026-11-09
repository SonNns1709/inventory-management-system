<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dòng tồn kho (product × warehouse). Chỉ dùng cho test/dữ liệu thử: dòng tạo bằng factory KHÔNG có bản ghi thẻ kho
 * (stock_movements) đi kèm. Ở nghiệp vụ thật, tồn chỉ thay đổi qua InventoryService::applyMovement().
 *
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn () => ['quantity' => $quantity]);
    }
}
