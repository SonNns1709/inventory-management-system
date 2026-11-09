<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // warehouses.code: VARCHAR(20), duy nhất
            'code' => fake()->unique()->numerify('KHO-####'),
            'name' => 'Kho thử nghiệm '.fake()->unique()->numerify('####'),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    /** Kho đã ngừng hoạt động (is_active = 0) — không chọn được khi lập phiếu mới. */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
