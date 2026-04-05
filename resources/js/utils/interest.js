/** Simple interest: P × (R/100) × (days/365). Principal in cents. */
export function simpleInterestCents(principalCents, annualRatePercent, days) {
    const p = Number(principalCents) || 0;
    const r = Number(annualRatePercent) || 0;
    const d = Number(days) || 0;
    if (p <= 0 || r <= 0 || d <= 0) return 0;
    return Math.round(p * (r / 100) * (d / 365));
}

export function annualInterestCents(principalCents, annualRatePercent) {
    return simpleInterestCents(principalCents, annualRatePercent, 365);
}

export function monthlyInterestCents(principalCents, annualRatePercent) {
    return Math.round(annualInterestCents(principalCents, annualRatePercent) / 12);
}

export function dollarsToCents(dollars) {
    return Math.round((Number(dollars) || 0) * 100);
}
