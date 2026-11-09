<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // products.sku: VARCHAR(30), duy nhất (BR02.05-2)
            'sku' => strtoupper(fake()->unique()->bothify('SP-????-####')),
            'name' => fake()->unique()->words(3, true),
            'category_id' => Category::factory(),
            'unit' => fake()->randomElement(['cái', 'hộp', 'thùng', 'bộ']),
            'price' => fake()->numberBetween(10, 500) * 1000,
            // avg_cost do hệ thống tự tính khi xác nhận phiếu nhập; sản phẩm mới chưa có giá vốn
            'avg_cost' => 0,
            'min_stock_level' => 10,
            'description' => null,
            'is_active' => true,
        ];
    }

    /** Sản phẩm đã ngừng kinh doanh (is_active = 0). */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Đặt ngưỡng cảnh báo tồn thấp (BR02.05-5). */
    public function minStock(int $level): static
    {
        return $this->state(fn () => ['min_stock_level' => $level]);
    }

    /** Đặt giá vốn bình quân hiện tại (chuỗi decimal, ví dụ '12500.0000'). */
    public function avgCost(string|int|float $cost): static
    {
        return $this->state(fn () => ['avg_cost' => $cost]);
    }
}
