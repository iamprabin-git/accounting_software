export function toIsoDate(y, monthIndex, day) {
    return `${y}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function parseIsoDate(iso) {
    if (!iso || typeof iso !== 'string') {
        return null;
    }
    const [y, m, d] = iso.split('-').map(Number);
    if (!y || !m || !d) {
        return null;
    }
    return { y, m: m - 1, d };
}

export function buildMonthCells(year, monthIndex) {
    const first = new Date(year, monthIndex, 1);
    const lastDay = new Date(year, monthIndex + 1, 0).getDate();
    const pad = first.getDay();
    const cells = [];
    for (let i = 0; i < pad; i += 1) {
        cells.push({ kind: 'pad' });
    }
    for (let day = 1; day <= lastDay; day += 1) {
        cells.push({
            kind: 'day',
            iso: toIsoDate(year, monthIndex, day),
            day,
        });
    }
    return cells;
}

export const WEEKDAY_LABELS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

/** Sunday = 0 … Saturday = 6 (local timezone). */
export function isoWeekday(iso) {
    const parsed = parseIsoDate(iso);
    if (!parsed) {
        return null;
    }
    return new Date(parsed.y, parsed.m, parsed.d).getDay();
}

export function isWeekendIso(iso) {
    const w = isoWeekday(iso);
    return w === 0 || w === 6;
}

/** True if postings are allowed on this calendar date for the given sets. */
export function isWorkingDayIso(iso, holidaySet, workingOverrideSet) {
    if (!iso || holidaySet.has(iso)) {
        return false;
    }
    if (isWeekendIso(iso)) {
        return workingOverrideSet.has(iso);
    }
    return true;
}
