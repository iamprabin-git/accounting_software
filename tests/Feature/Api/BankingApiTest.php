<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_login_returns_bearer_token(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create([
            'email' => 'owner@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $res = $this->postJson('/api/v1/auth/token', [
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $res->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'abilities',
                'user' => ['id', 'role', 'company_id'],
            ]);

        $this->assertNotEmpty($res->json('token'));
    }

    public function test_summary_requires_company_id_for_admin(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin, ['banking:read']);

        $this->getJson('/api/v1/banking/summary')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Admin requests must include company_id (query, JSON body, or X-Company-Id header).',
            ]);

        $this->getJson('/api/v1/banking/summary?company_id='.$company->id)
            ->assertOk()
            ->assertJsonPath('data.members_approved', 0);

        $this->withHeaders(['X-Company-Id' => (string) $company->id])
            ->getJson('/api/v1/banking/summary')
            ->assertOk()
            ->assertJsonPath('meta.company_id', $company->id);
    }

    public function test_company_owner_summary_without_query_company_id(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        Sanctum::actingAs($owner, ['banking:read']);

        $this->getJson('/api/v1/banking/summary')
            ->assertOk()
            ->assertJsonPath('meta.company_id', $company->id);
    }

    public function test_members_and_positions_paginated(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        Member::query()->create([
            'company_id' => $company->id,
            'reference_code' => 'REF-'.uniqid(),
            'name' => 'A Member',
            'status' => Member::STATUS_APPROVED,
            'created_by_user_id' => $owner->id,
            'approved_by_user_id' => $owner->id,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($owner, ['banking:read']);

        $this->getJson('/api/v1/banking/members?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/banking/positions?category=loan')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_end_user_cannot_request_token(): void
    {
        $company = Company::factory()->create();
        $member = User::factory()->create([
            'company_id' => $company->id,
            'role' => User::ROLE_END_USER,
            'email' => 'mem@example.com',
            'password' => bcrypt('p'),
        ]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'mem@example.com',
            'password' => 'p',
        ])->assertStatus(403);
    }
}
