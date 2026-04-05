<?php

namespace Tests\Unit;

use App\Models\FinancialPosition;
use PHPUnit\Framework\TestCase;

class FinancialPositionInterestTest extends TestCase
{
    public function test_annual_simple_interest_matches_formula(): void
    {
        // $10,000 at 12% p.a. = $1,200
        $cents = FinancialPosition::interestCentsForRateAndPrincipal(1_000_000, 12.0, 365);
        $this->assertSame(120_000, $cents);
    }

    public function test_monthly_is_twelfth_of_annual(): void
    {
        $p = new FinancialPosition([
            'principal_cents' => 1_000_000,
            'annual_interest_rate_percent' => 12.0,
        ]);
        $this->assertSame(120_000, $p->annualInterestCents());
        $this->assertSame(10_000, $p->monthlyInterestCents());
    }

    public function test_interest_for_ninety_days(): void
    {
        $cents = FinancialPosition::interestCentsForRateAndPrincipal(1_000_000, 10.0, 90);
        $this->assertSame(24_658, $cents);
    }
}
