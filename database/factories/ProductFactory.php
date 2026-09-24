<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        static $urut = 0;
        $urut++;

        return [
            'code' => 'ES-'.str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            'name' => 'Es '.fake()->unique()->word(),
            'supplier_id' => null,
            'variant' => fake()->randomElement(['cup', 'stik', 'cone', 'lainnya']),
            'unit' => 'pcs',
            'cost_price' => 2500,
            'default_selling_price' => 5000,
            'default_fee_type' => 'nominal',
            'default_fee_value' => 500,
            'min_stock' => 20,
            'is_active' => true,
        ];
    }

    public function feePersen(int $persen = 10): static
    {
        return $this->state(fn () => [
            'default_fee_type' => 'percent',
            'default_fee_value' => $persen,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
