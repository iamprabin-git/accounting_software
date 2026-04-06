<?php

namespace Tests\Feature;

use App\Models\AccountingAuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use LogicException;
use Tests\TestCase;

class AccountingAuditTrailHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_stores_hash_chain_and_actor_metadata(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)->post(route('company.period.close'), [
            'close_lock_date' => '2026-03-31',
            'close_reason' => 'Close period test.',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('company.period.reopen'), [
            'reopen_to_date' => '',
            'reopen_reason' => 'Reopen period test.',
        ])->assertRedirect();

        $first = AccountingAuditLog::query()->where('company_id', $company->id)->orderBy('id')->first();
        $second = AccountingAuditLog::query()->where('company_id', $company->id)->orderBy('id')->skip(1)->first();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotNull($first->event_hash);
        $this->assertNotNull($second->event_hash);
        $this->assertSame($first->event_hash, $second->previous_event_hash);
        $this->assertNotNull($first->actor_ip);
    }

    public function test_audit_logs_are_immutable(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $log = AccountingAuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'action' => 'test.action',
            'event_hash' => str_repeat('a', 64),
            'created_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'changed.action']);
    }

    public function test_audit_trail_csv_export_works(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        AccountingAuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'action' => 'journal.test_event',
            'event_hash' => str_repeat('b', 64),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get(route('audit-trail.export.csv'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('journal.test_event', $response->streamedContent());
    }

    public function test_audit_integrity_verification_detects_tampering(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)->post(route('company.period.close'), [
            'close_lock_date' => '2026-03-31',
            'close_reason' => 'Close period for integrity test.',
        ])->assertRedirect();

        $log = AccountingAuditLog::query()->where('company_id', $company->id)->firstOrFail();
        DB::table('accounting_audit_logs')
            ->where('id', $log->id)
            ->update(['event_hash' => str_repeat('f', 64)]);

        $response = $this->actingAs($owner)->get(route('audit-trail.index'));
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Accounting/AuditTrail/Index')
            ->where('integrity.valid', false)
            ->where('integrity.first_broken_event_id', $log->id)
            ->where('integrity.first_broken_reason', 'event_hash_mismatch'));
    }

    public function test_verify_now_creates_integrity_checkpoint_log(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)
            ->post(route('audit-trail.verify-now'))
            ->assertRedirect();

        $this->assertDatabaseHas('accounting_audit_logs', [
            'company_id' => $company->id,
            'action' => 'audit.integrity_verified',
            'user_id' => $owner->id,
        ]);
    }
}
