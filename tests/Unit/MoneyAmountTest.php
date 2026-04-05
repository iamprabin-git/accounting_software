<?php

namespace Tests\Unit;

use App\Support\MoneyAmount;
use PHPUnit\Framework\TestCase;

class MoneyAmountTest extends TestCase
{
    public function test_decimal_string_avoids_float_drift(): void
    {
        $this->assertSame(1999, MoneyAmount::numericInputToCents('19.99'));
        $this->assertSame(1, MoneyAmount::numericInputToCents('0.01'));
        $this->assertSame(123456, MoneyAmount::numericInputToCents('1234.56'));
    }

    public function test_strips_thousands_separators(): void
    {
        $this->assertSame(123456, MoneyAmount::numericInputToCents('1,234.56'));
    }

    public function test_integer_whole_units(): void
    {
        $this->assertSame(5000, MoneyAmount::numericInputToCents(50));
    }
}
