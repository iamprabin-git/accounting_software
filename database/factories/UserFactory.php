<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'subscription_ends_at' => null,
            'company_id' => null,
            'role' => User::ROLE_END_USER,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function companyOwner(?Company $company = null): static
    {
        return $this->state(function (array $attributes) use ($company) {
            $company ??= Company::factory()->create();

            return [
                'company_id' => $company->id,
                'role' => User::ROLE_COMPANY,
            ];
        });
    }

    public function staff(?Company $company = null): static
    {
        return $this->state(function (array $attributes) use ($company) {
            $company ??= Company::factory()->create();

            return [
                'company_id' => $company->id,
                'role' => User::ROLE_STAFF,
            ];
        });
    }

    public function endUser(?Company $company = null): static
    {
        return $this->state(function (array $attributes) use ($company) {
            $company ??= Company::factory()->create();

            return [
                'company_id' => $company->id,
                'role' => User::ROLE_END_USER,
            ];
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
