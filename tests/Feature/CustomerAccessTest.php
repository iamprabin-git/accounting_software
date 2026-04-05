<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_user_with_expired_subscription_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'subscription_ends_at' => now()->subDay(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_active_user_with_future_subscription_can_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivate_expired_command_sets_inactive(): void
    {
        $active = User::factory()->create([
            'is_active' => true,
            'subscription_ends_at' => now()->subHour(),
        ]);

        $this->artisan('subscriptions:deactivate-expired')->assertSuccessful();

        $this->assertFalse($active->fresh()->is_active);
    }
}
