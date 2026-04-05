<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialPosition extends Model
{
    use BelongsToCompany;

    public const CATEGORY_LOAN = 'loan';

    public const CATEGORY_INVESTMENT = 'investment';

    public const CATEGORY_SAVINGS = 'savings';

    public const LOAN_WORKFLOW_PENDING_APPROVAL = 'pending_approval';

    public const LOAN_WORKFLOW_ACTIVE = 'active';

    public const LOAN_WORKFLOW_REJECTED = 'rejected';

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_LOAN,
            self::CATEGORY_INVESTMENT,
            self::CATEGORY_SAVINGS,
        ];
    }

    protected $fillable = [
        'company_id',
        'member_id',
        'loan_product_id',
        'loan_workflow_status',
        'sanctioned_amount_cents',
        'product_account_sequence',
        'savings_product_id',
        'savings_workflow_status',
        'savings_product_account_sequence',
        'account_number',
        'category',
        'title',
        'principal_cents',
        'annual_interest_rate_percent',
        'start_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_cents' => 'integer',
            'sanctioned_amount_cents' => 'integer',
            'annual_interest_rate_percent' => 'decimal:4',
            'start_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function savingsProduct(): BelongsTo
    {
        return $this->belongsTo(SavingsProduct::class, 'savings_product_id');
    }

    public function accruals(): HasMany
    {
        return $this->hasMany(FinancialPositionAccrual::class, 'financial_position_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(FinancialPositionMovement::class, 'financial_position_id');
    }

    public function usesBankingMonthlyRate(): bool
    {
        return in_array($this->category, [self::CATEGORY_LOAN, self::CATEGORY_SAVINGS], true);
    }

    public function requiresApprovedMember(): bool
    {
        return in_array($this->category, [
            self::CATEGORY_LOAN,
            self::CATEGORY_SAVINGS,
            self::CATEGORY_INVESTMENT,
        ], true);
    }

    /**
     * Loan, savings, and investment finance require a linked approved member (individual ledger).
     */
    public function memberApprovedForFinance(): bool
    {
        if (! $this->requiresApprovedMember()) {
            return true;
        }

        if ($this->member_id === null) {
            return false;
        }

        return $this->member?->isApproved() ?? false;
    }

    /**
     * Loan tied to a catalog product uses approval workflow and product-style account numbers.
     */
    public function usesStructuredLoanFlow(): bool
    {
        return $this->category === self::CATEGORY_LOAN && $this->loan_product_id !== null;
    }

    public function isLoanPendingApproval(): bool
    {
        return $this->usesStructuredLoanFlow()
            && $this->loan_workflow_status === self::LOAN_WORKFLOW_PENDING_APPROVAL;
    }

    public function isLoanRejected(): bool
    {
        return $this->usesStructuredLoanFlow()
            && $this->loan_workflow_status === self::LOAN_WORKFLOW_REJECTED;
    }

    /**
     * Movements, disbursements, ledger posting, and account-number lookup are allowed.
     */
    public function isLoanOperational(): bool
    {
        if ($this->category !== self::CATEGORY_LOAN) {
            return true;
        }

        if (! $this->usesStructuredLoanFlow()) {
            return true;
        }

        return $this->loan_workflow_status === self::LOAN_WORKFLOW_ACTIVE;
    }

    public function proposedAccountNumber(): ?string
    {
        if (! $this->usesStructuredLoanFlow() || $this->product_account_sequence === null) {
            return null;
        }

        $this->loadMissing('loanProduct');
        if ($this->loanProduct === null) {
            return null;
        }

        return $this->loanProduct->product_code.'-'.str_pad((string) $this->product_account_sequence, 4, '0', STR_PAD_LEFT);
    }

    public function usesStructuredSavingsFlow(): bool
    {
        return $this->category === self::CATEGORY_SAVINGS && $this->savings_product_id !== null;
    }

    public function isSavingsPendingApproval(): bool
    {
        return $this->usesStructuredSavingsFlow()
            && $this->savings_workflow_status === self::LOAN_WORKFLOW_PENDING_APPROVAL;
    }

    public function isSavingsRejected(): bool
    {
        return $this->usesStructuredSavingsFlow()
            && $this->savings_workflow_status === self::LOAN_WORKFLOW_REJECTED;
    }

    public function isSavingsOperational(): bool
    {
        if ($this->category !== self::CATEGORY_SAVINGS) {
            return true;
        }

        if (! $this->usesStructuredSavingsFlow()) {
            return true;
        }

        return $this->savings_workflow_status === self::LOAN_WORKFLOW_ACTIVE;
    }

    public function proposedSavingsAccountNumber(): ?string
    {
        if (! $this->usesStructuredSavingsFlow() || $this->savings_product_account_sequence === null) {
            return null;
        }

        $this->loadMissing('savingsProduct');
        if ($this->savingsProduct === null) {
            return null;
        }

        return $this->savingsProduct->product_code.'-'.str_pad((string) $this->savings_product_account_sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Loan or savings position tied to a catalog product (approval + product-style account number).
     */
    public function usesStructuredCatalogWorkflow(): bool
    {
        return $this->usesStructuredLoanFlow() || $this->usesStructuredSavingsFlow();
    }

    /**
     * Whether structured catalog flows are allowed to transact (approved / active account number).
     */
    public function isStructuredCatalogOperational(): bool
    {
        if ($this->usesStructuredLoanFlow()) {
            return $this->isLoanOperational();
        }

        if ($this->usesStructuredSavingsFlow()) {
            return $this->isSavingsOperational();
        }

        return true;
    }

    public function proposedCatalogAccountNumber(): ?string
    {
        return $this->proposedAccountNumber() ?? $this->proposedSavingsAccountNumber();
    }

    /**
     * Simple interest for one full year: P × (R/100).
     */
    public function annualInterestCents(): int
    {
        return self::interestCentsForRateAndPrincipal(
            $this->principal_cents,
            (float) $this->annual_interest_rate_percent,
            365
        );
    }

    /**
     * Simple interest for one average month: annual / 12.
     */
    public function monthlyInterestCents(): int
    {
        return (int) round($this->annualInterestCents() / 12);
    }

    /**
     * Simple interest for a number of days: P × (R/100) × (days/365).
     */
    public function interestCentsForDays(int $days): int
    {
        return self::interestCentsForRateAndPrincipal(
            $this->principal_cents,
            (float) $this->annual_interest_rate_percent,
            $days
        );
    }

    public static function interestCentsForRateAndPrincipal(int $principalCents, float $annualRatePercent, int $days): int
    {
        if ($principalCents === 0 || $annualRatePercent == 0.0 || $days <= 0) {
            return 0;
        }

        return (int) round($principalCents * ($annualRatePercent / 100) * ($days / 365));
    }
}
