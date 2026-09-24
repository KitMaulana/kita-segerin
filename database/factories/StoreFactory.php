<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        static $urut = 0;
        $urut++;

        return [
            'code' => 'TK-'.str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            'name' => 'Koperasi '.fake()->unique()->lastName(),
            'type' => 'koperasi',
            'contact_person' => fake()->name(),
            'phone' => '08'.fake()->numerify('##########'),
            'address' => fake()->address(),
            'payment_term_days' => 7,
            'is_active' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
