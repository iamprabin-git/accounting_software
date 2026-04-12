<?php

namespace Tests\Feature\Api;

use App\Models\BankingWebhookSubscription;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankingApiWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{Company, User, ChartAccount, ChartAccount}
     */
    private function companyOwnerWithTwoAccounts(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $cash = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1000',
            'name' => 'Cash',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $bank = ChartAccount::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'code' => '1010',
            'name' => 'Bank',
            'type' => ChartAccount::TYPE_ASSET,
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        return [$company, $owner, $cash, $bank];
    }

    public function test_two_line_journal_via_api(): void
    {
        [$company, $owner, $cash, $bank] = $this->companyOwnerWithTwoAccounts();

        Sanctum::actingAs($owner, ['banking:journal']);

        $res = $this->postJson('/api/v1/banking/journals/two-line', [
            'transaction_date' => '2026-05-01',
            'memo' => 'API test journal',
            'reference' => 'API-1',
            'amount' => '10.00',
            'debit_chart_account_id' => $bank->id,
            'credit_chart_account_id' => $cash->id,
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
            'memo' => 'API test journal',
        ]);
    }

    public function test_transfer_endpoint_credits_from_and_debits_to(): void
    {
        [$company, $owner, $cash, $bank] = $this->companyOwnerWithTwoAccounts();

        Sanctum::actingAs($owner, ['banking:journal']);

        $this->postJson('/api/v1/banking/transfers', [
            'transaction_date' => '2026-05-04',
            'memo' => 'Move to bank',
            'amount' => '5.00',
            'from_chart_account_id' => $cash->id,
            'to_chart_account_id' => $bank->id,
        ])->assertCreated();

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $company->id,
        ]);
    }

    public function test_read_only_token_cannot_post_journal(): void
    {
        [$company, $owner, $cash, $bank] = $this->companyOwnerWithTwoAccounts();

        Sanctum::actingAs($owner, ['banking:read']);

        $this->postJson('/api/v1/banking/journals/two-line', [
            'transaction_date' => '2026-05-01',
            'memo' => 'X',
            'amount' => '1.00',
            'debit_chart_account_id' => $bank->id,
            'credit_chart_account_id' => $cash->id,
        ])->assertStatus(403);
    }

    public function test_revoke_current_token(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $token = $owner->createToken('t', ['banking:read']);
        $plain = $token->plainTextToken;

        $this->withToken($plain)
            ->deleteJson('/api/v1/auth/tokens/current')
            ->assertOk();

        $this->assertSame(0, $owner->fresh()->tokens()->count());

        Auth::forgetGuards();

        $this->withToken($plain)
            ->getJson('/api/v1/banking/summary')
            ->assertUnauthorized();

        Auth::forgetGuards();

        $this->withToken('invalid-plain-token')
            ->getJson('/api/v1/banking/summary')
            ->assertUnauthorized();
    }

    public function test_webhook_delivers_on_posted_journal(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response('ok', 200),
        ]);

        [$company, $owner, $cash, $bank] = $this->companyOwnerWithTwoAccounts();

        BankingWebhookSubscription::query()->create([
            'company_id' => $company->id,
            'name' => 'Test hook',
            'url' => 'https://hooks.example.test/receive',
            'secret' => 'test-secret-plain',
            'events' => ['journal.posted'],
            'is_active' => true,
        ]);

        Sanctum::actingAs($owner, ['banking:journal']);

        $this->postJson('/api/v1/banking/journals/two-line', [
            'transaction_date' => '2026-05-05',
            'memo' => 'Webhook trigger',
            'amount' => '1.00',
            'debit_chart_account_id' => $bank->id,
            'credit_chart_account_id' => $cash->id,
        ])->assertCreated();

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://hooks.example.test/receive')
                && $request->hasHeader('X-Banking-Signature')
                && str_contains($request->body(), 'journal.posted');
        });
    }

    public function test_webhook_api_requires_scope(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        Sanctum::actingAs($owner, ['banking:read']);

        $this->getJson('/api/v1/banking/webhooks?company_id='.$company->id)
            ->assertStatus(403);
    }
}
