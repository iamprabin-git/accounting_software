<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_and_update_company_profile_with_logo(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create([
            'name' => 'Acme Ltd',
            'address' => 'Old street',
            'phone' => '111',
        ]);

        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('company.profile.edit', absolute: false))
            ->assertOk();

        $file = UploadedFile::fake()->image('logo.png', 200, 80);

        $this->actingAs($owner)
            ->post(route('company.profile.update', absolute: false), [
                'name' => 'Acme Holdings',
                'address' => "1 New Road\nCity",
                'phone' => '+1 555 0001',
                'logo' => $file,
                'remove_logo' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.profile.edit', absolute: false));

        $company->refresh();
        $this->assertSame('Acme Holdings', $company->name);
        $this->assertStringContainsString('1 New Road', (string) $company->address);
        $this->assertSame('+1 555 0001', $company->phone);
        $this->assertNotNull($company->logo_path);
        Storage::disk('public')->assertExists($company->logo_path);
    }

    public function test_company_owner_can_update_bank_payment_details_for_portal(): void
    {
        $company = Company::factory()->create([
            'name' => 'PayCo',
            'portal_show_payment_details' => true,
        ]);

        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.profile.update', absolute: false), [
                'name' => 'PayCo',
                'address' => '',
                'phone' => '',
                'bank_payment_details' => "Nepal Bank Ltd\nA/C: 0123456789\nBranch: Kathmandu",
                'portal_show_payment_details' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.profile.edit', absolute: false));

        $company->refresh();
        $this->assertStringContainsString('Nepal Bank Ltd', (string) $company->bank_payment_details);
        $this->assertTrue($company->portal_show_payment_details);
    }

    public function test_staff_cannot_access_company_profile_routes(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('company.profile.edit', absolute: false))
            ->assertForbidden();
    }
}
