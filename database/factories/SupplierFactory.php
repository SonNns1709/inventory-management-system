<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('09########'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'tax_code' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }

    /** Nhà cung cấp đã ngừng hợp tác (is_active = 0). */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
