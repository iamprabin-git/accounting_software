<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'plan' => Company::PLAN_ENTERPRISE,
            'feature_inventory_enabled' => true,
            'feature_members_enabled' => true,
            'address' => fake()->optional()->streetAddress(),
            'phone' => fake()->optional()->phoneNumber(),
            'logo_path' => null,
        ];
    }

    public function starter(): static
    {
        return $this->state(fn () => [
            'plan' => Company::PLAN_STARTER,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn () => [
            'plan' => Company::PLAN_PROFESSIONAL,
        ]);
    }
}
