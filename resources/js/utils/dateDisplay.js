import NepaliDate from 'nepali-date-converter';

export function parseToDate(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    const d = value instanceof Date ? value : new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
}

export function formatDisplayDate(value) {
    const d = parseToDate(value);
    if (!d) {
        return '';
    }
    return d.toLocaleDateString('en-GB', { dateStyle: 'medium' });
}

export function formatDisplayDateTime(value) {
    const d = parseToDate(value);
    if (!d) {
        return '';
    }
    return d.toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

/**
 * Normalize "YYYY-MM-DD HH:mm:ss" or ISO-ish strings for Date parsing.
 */
function normalizeIsoish(at) {
    if (!at) {
        return null;
    }
    const s = String(at).trim();
    return s.includes('T') ? s : s.replace(' ', 'T');
}

/**
 * Gregorian (AD) + Bikram Sambat (BS) for printed statements.
 *
 * @returns {{ adLine: string, bsLine: string } | null}
 */
export function formatStatementAtParts(at) {
    const isoish = normalizeIsoish(at);
    if (!isoish) {
        return null;
    }
    const d = parseToDate(isoish);
    if (!d) {
        return null;
    }
    const adLine = d.toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
    let bsLine = '';
    try {
        const np = new NepaliDate(d);
        bsLine = np.format('ddd, DD MMMM YYYY', 'en');
    } catch {
        bsLine = '';
    }
    return { adLine, bsLine };
}
