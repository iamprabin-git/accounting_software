<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_team_index(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $response = $this->actingAs($owner)
            ->get(route('company.team.index', absolute: false));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('staffMembers')
            ->has('endUserMembers'));
    }

    public function test_staff_cannot_access_team_routes(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('company.team.index', absolute: false))
            ->assertForbidden();
    }

    public function test_new_staff_user_is_inactive_until_owner_activates(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.team.store', absolute: false), [
                'name' => 'New Staff',
                'email' => 'staff-new@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_STAFF,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $staff = User::query()->where('email', 'staff-new@example.com')->firstOrFail();

        $this->assertFalse($staff->is_active);

        $this->actingAs($owner)
            ->post(route('company.team.activate', ['member' => $staff->id], absolute: false))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue($staff->fresh()->is_active);
    }
}
