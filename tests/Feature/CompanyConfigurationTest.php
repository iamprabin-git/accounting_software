<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class CompanyConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_and_update_configuration(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->get(route('company.configuration.edit', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Company/Configuration/Index'));

        $this->actingAs($owner)
            ->post(route('company.configuration.update', absolute: false), [
                'enforce_holiday_blackout' => false,
                'cbs_internal_notes' => 'Escalate to ops',
                'dual_approval_threshold' => '1000',
                'backup_snapshots_root_folder' => '/var/backups/cbs',
                'backup_restore_instructions' => 'mysql < dump.sql',
                'backup_recorded_snapshots' => [],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.configuration.edit', absolute: false));

        $company->refresh();
        $this->assertFalse($company->cbsHolidayBlackoutEnabled());
        $this->assertSame('Escalate to ops', $company->normalizedCbsConfiguration()['internal_notes']);
        $this->assertSame(100_000, (int) $company->dual_approval_threshold_cents);
        $this->assertSame('/var/backups/cbs', $company->normalizedBackupConfiguration()['snapshots_root_folder']);
        $this->assertSame('mysql < dump.sql', $company->normalizedBackupConfiguration()['restore_instructions']);

        $this->actingAs($owner)
            ->post(route('company.holidays.store', absolute: false), [
                'holiday_date' => '2026-01-01',
                'name' => 'New Year',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('company.holidays.index', absolute: false));

        $this->assertTrue(
            CompanyHoliday::query()
                ->where('company_id', $company->id)
                ->whereDate('holiday_date', '2026-01-01')
                ->exists()
        );
    }

    public function test_staff_can_view_company_configuration_but_cannot_update(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->get(route('company.configuration.edit', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Company/Configuration/Index'));

        $this->actingAs($staff)
            ->post(route('company.configuration.update', absolute: false), [
                'enforce_holiday_blackout' => false,
                'cbs_internal_notes' => 'Should not save',
                'dual_approval_threshold' => '',
                'backup_snapshots_root_folder' => '',
                'backup_restore_instructions' => '',
                'backup_recorded_snapshots' => [],
            ])
            ->assertForbidden();

        $company->refresh();
        $this->assertTrue($company->cbsHolidayBlackoutEnabled());
        $this->assertNotSame(
            'Should not save',
            $company->normalizedCbsConfiguration()['internal_notes'],
        );
    }

    public function test_company_owner_can_download_portable_backup_zip(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $response = $this->actingAs($owner)
            ->post(route('company.configuration.portable-backup-zip', absolute: false));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('attachment', $disposition);
        $base = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $base);
        $path = $base->getFile()->getPathname();
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertNotEmpty($content);
        if (class_exists(\ZipArchive::class)) {
            $this->assertStringStartsWith('PK', $content, 'Expected ZIP payload');
        }
    }

    public function test_staff_cannot_download_portable_backup_zip(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->staff($company)->create();

        $this->actingAs($staff)
            ->post(route('company.configuration.portable-backup-zip', absolute: false))
            ->assertForbidden();
    }

    public function test_configuration_requires_tax_liability_when_withholding_percent_positive(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('company.configuration.update', absolute: false), [
                'enforce_holiday_blackout' => true,
                'cbs_internal_notes' => '',
                'dual_approval_threshold' => '',
                'backup_snapshots_root_folder' => '',
                'backup_restore_instructions' => '',
                'backup_recorded_snapshots' => [],
                'deposit_interest_withholding_tax_percent' => '12.5',
            ])
            ->assertSessionHasErrors('deposit_interest_tax_payable_chart_account_id');
    }

    public function test_configuration_preserves_deposit_withholding_when_tax_fields_omitted(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $taxAcct = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '2350',
            'name' => 'Withholding tax payable',
            'type' => ChartAccount::TYPE_LIABILITY,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $company->forceFill([
            'cbs_configuration' => [
                'enforce_holiday_blackout' => true,
                'internal_notes' => 'x',
                'deposit_interest_withholding_tax_percent' => 7,
                'deposit_interest_tax_payable_chart_account_id' => $taxAcct->id,
            ],
        ])->save();

        $this->actingAs($owner)
            ->post(route('company.configuration.update', absolute: false), [
                'enforce_holiday_blackout' => false,
                'cbs_internal_notes' => 'Still here',
                'dual_approval_threshold' => '',
                'backup_snapshots_root_folder' => '',
                'backup_restore_instructions' => '',
                'backup_recorded_snapshots' => [],
            ])
            ->assertSessionHasNoErrors();

        $company->refresh();
        $cbs = $company->normalizedCbsConfiguration();
        $this->assertSame(7.0, $cbs['deposit_interest_withholding_tax_percent']);
        $this->assertSame($taxAcct->id, $cbs['deposit_interest_tax_payable_chart_account_id']);
        $this->assertSame('Still here', $cbs['internal_notes']);
        $this->assertFalse($company->cbsHolidayBlackoutEnabled());
    }
}
