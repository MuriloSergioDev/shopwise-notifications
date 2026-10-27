<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
final class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => (string) Str::ulid(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
        ];
    }
}
