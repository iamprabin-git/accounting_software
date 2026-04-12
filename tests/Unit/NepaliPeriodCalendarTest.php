<?php

namespace Tests\Unit;

use App\Services\NepaliPeriodCalendar;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class NepaliPeriodCalendarTest extends TestCase
{
    private NepaliPeriodCalendar $cal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cal = new NepaliPeriodCalendar;
    }

    public function test_chaitra_2082_last_day_is_2026_04_13(): void
    {
        $this->assertTrue($this->cal->isLastDayOfNepaliMonth(Carbon::parse('2026-04-13')));
        $this->assertTrue($this->cal->isNepaliCalendarYearEnd(Carbon::parse('2026-04-13')));
        $this->assertSame(
            ['year' => 2082, 'month' => 12, 'day' => 30],
            $this->cal->adToBs(Carbon::parse('2026-04-13'))
        );
    }

    public function test_mid_chaitra_is_not_month_end(): void
    {
        $this->assertFalse($this->cal->isLastDayOfNepaliMonth(Carbon::parse('2026-03-31')));
        $this->assertFalse($this->cal->isNepaliCalendarYearEnd(Carbon::parse('2026-03-31')));
    }

    public function test_nepali_fiscal_year_start_for_ashadh_close(): void
    {
        $close = Carbon::parse('2025-07-16');
        $this->assertTrue($this->cal->isNepaliFiscalYearEnd($close));
        $start = $this->cal->nepaliFiscalYearStartAdForAshadhClose($close);
        $this->assertNotNull($start);
        $this->assertSame('2024-07-16', $start->toDateString());
    }

    public function test_fiscal_quarter_end_months(): void
    {
        $this->assertTrue($this->cal->isNepaliFiscalQuarterEnd(Carbon::parse('2025-07-16')));
        $this->assertFalse($this->cal->isNepaliFiscalQuarterEnd(Carbon::parse('2025-07-14')));
    }
}
