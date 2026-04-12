<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Nilambar\NepaliDate\NepaliDate;

/**
 * Bikram Sambat (Nepali) boundaries for period close: month, fiscal quarter, BS year, and fiscal year (Shrawan–Ashadh).
 *
 * Conversion uses ernilambar/nepali-date (AD range roughly 1944–2033; BS tables to 2090).
 */
final class NepaliPeriodCalendar
{
    private NepaliDate $nepaliDate;

    public function __construct(?NepaliDate $nepaliDate = null)
    {
        $this->nepaliDate = $nepaliDate ?? new NepaliDate;
    }

    /**
     * @return array{year: int, month: int, day: int}|null
     */
    public function adToBs(CarbonInterface $date): ?array
    {
        $r = $this->nepaliDate->convertAdToBs(
            (int) $date->year,
            (int) $date->month,
            (int) $date->day
        );
        if (empty($r) || ! isset($r['year'], $r['month'], $r['day'])) {
            return null;
        }

        return [
            'year' => (int) $r['year'],
            'month' => (int) $r['month'],
            'day' => (int) $r['day'],
        ];
    }

    public function bsToAdStartOfDay(int $bsYear, int $bsMonth, int $bsDay): ?Carbon
    {
        $r = $this->nepaliDate->convertBsToAd($bsYear, $bsMonth, $bsDay);
        if (empty($r) || ! isset($r['year'], $r['month'], $r['day'])) {
            return null;
        }

        return Carbon::create((int) $r['year'], (int) $r['month'], (int) $r['day'])->startOfDay();
    }

    public function lastAdDateOfBsMonth(int $bsYear, int $bsMonth): ?Carbon
    {
        for ($day = 32; $day >= 1; $day--) {
            $ad = $this->bsToAdStartOfDay($bsYear, $bsMonth, $day);
            if ($ad !== null) {
                return $ad;
            }
        }

        return null;
    }

    public function isLastDayOfNepaliMonth(CarbonInterface $date): bool
    {
        $bs = $this->adToBs($date);
        if ($bs === null) {
            return false;
        }
        $last = $this->lastAdDateOfBsMonth($bs['year'], $bs['month']);
        if ($last === null) {
            return false;
        }

        return $date->toDateString() === $last->toDateString();
    }

    /**
     * Nepal fiscal quarters (Shrawan–Ashadh year): Ashwin, Paush, Chaitra, Ashadh month-ends.
     *
     * @var list<int>
     */
    private const NEPALI_FISCAL_QUARTER_END_MONTHS = [3, 6, 9, 12];

    public function isNepaliFiscalQuarterEnd(CarbonInterface $date): bool
    {
        $bs = $this->adToBs($date);
        if ($bs === null) {
            return false;
        }
        if (! in_array($bs['month'], self::NEPALI_FISCAL_QUARTER_END_MONTHS, true)) {
            return false;
        }

        return $this->isLastDayOfNepaliMonth($date);
    }

    /** Last day of Chaitra (BS calendar year end). */
    public function isNepaliCalendarYearEnd(CarbonInterface $date): bool
    {
        $bs = $this->adToBs($date);
        if ($bs === null) {
            return false;
        }
        if ($bs['month'] !== 12) {
            return false;
        }

        return $this->isLastDayOfNepaliMonth($date);
    }

    /** Last day of Ashadh (Nepal fiscal year end, BS months 1–12 with Ashadh = 3). */
    public function isNepaliFiscalYearEnd(CarbonInterface $date): bool
    {
        $bs = $this->adToBs($date);
        if ($bs === null) {
            return false;
        }
        if ($bs['month'] !== 3) {
            return false;
        }

        return $this->isLastDayOfNepaliMonth($date);
    }

    /**
     * First day of Shrawan for the fiscal year that ends on the given Ashadh month-end.
     * Fiscal year (Y/(Y+1) BS): Shrawan 1, BS year Y through last day of Ashadh, BS year Y+1.
     */
    public function nepaliFiscalYearStartAdForAshadhClose(CarbonInterface $closeDate): ?Carbon
    {
        if (! $this->isNepaliFiscalYearEnd($closeDate)) {
            return null;
        }
        $bs = $this->adToBs($closeDate);
        if ($bs === null || $bs['year'] < 2001) {
            return null;
        }

        return $this->bsToAdStartOfDay($bs['year'] - 1, 4, 1);
    }

    public function fiscalCloseReferenceKey(CarbonInterface $closeDate): ?string
    {
        if (! $this->isNepaliFiscalYearEnd($closeDate)) {
            return null;
        }
        $bs = $this->adToBs($closeDate);
        if ($bs === null) {
            return null;
        }

        return 'FY-CLOSE-BS'.$bs['year'];
    }
}
