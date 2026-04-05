/**
 * Format minor units (cents/paisa) as Nepalese Rupee (NPR).
 */
export function moneyFromCents(cents) {
    const n = (Number(cents) || 0) / 100;
    try {
        return new Intl.NumberFormat('en-NP', {
            style: 'currency',
            currency: 'NPR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n);
    } catch {
        const formatted = n.toLocaleString('en-NP', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        return `NPR ${formatted}`;
    }
}
