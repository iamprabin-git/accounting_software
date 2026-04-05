import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const KIND_LABEL = {
    loan_monthly: 'Loan interest (monthly)',
    savings_monthly: 'Savings interest (monthly accrual)',
    investment_manual: 'Investment return (manual)',
};

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

function LedgerFields({ accounts, data, setData, errors, prefix = '' }) {
    const p = (name) => (prefix ? `${prefix}.${name}` : name);
    const err = (name) => {
        const key = p(name);
        return errors[key] ?? errors[name];
    };
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <InputLabel value="Transaction date" />
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.transaction_date}
                    onChange={(e) =>
                        setData('transaction_date', e.target.value)
                    }
                    required
                />
                <InputError message={err('transaction_date')} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Debit account" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.debit_chart_account_id}
                    onChange={(e) =>
                        setData('debit_chart_account_id', e.target.value)
                    }
                    required
                >
                    <option value="">Select…</option>
                    {accounts.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.label}
                        </option>
                    ))}
                </select>
                <InputError
                    message={err('debit_chart_account_id')}
                    className="mt-1"
                />
            </div>
            <div>
                <InputLabel value="Credit account" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.credit_chart_account_id}
                    onChange={(e) =>
                        setData('credit_chart_account_id', e.target.value)
                    }
                    required
                >
                    <option value="">Select…</option>
                    {accounts.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.label}
                        </option>
                    ))}
                </select>
                <InputError
                    message={err('credit_chart_account_id')}
                    className="mt-1"
                />
            </div>
            <div>
                <InputLabel value="Reference (optional)" />
                <TextInput
                    className="mt-1 block w-full"
                    value={data.reference}
                    onChange={(e) => setData('reference', e.target.value)}
                />
                <InputError message={err('reference')} className="mt-1" />
            </div>
        </div>
    );
}

export default function Show({
    category,
    categoryLabel,
    position,
    year,
    accruals,
    savings_quarters,
    accounts,
    companies,
    currentCompanyId,
    workspace = 'full',
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const errors = page.props.errors ?? {};
    const flash = page.props.flash ?? {};
    const isAdmin = user.role === 'admin';
    const canApprove = user.can_approve_journals;

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const today = new Date().toISOString().slice(0, 10);
    const memberFinanceOk = position.member_finance_ok;

    const syncForm = useForm({
        year: String(year),
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    const manualForm = useForm({
        year: String(year),
        month: '1',
        amount: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            syncForm.setData('company_id', String(currentCompanyId));
            manualForm.setData('company_id', String(currentCompanyId));
        }
    }, [currentCompanyId, isAdmin]);

    useEffect(() => {
        syncForm.setData('year', String(year));
        manualForm.setData('year', String(year));
    }, [year]);

    const goYear = (y) => {
        router.get(
            route('finance.positions.show', {
                category,
                position: position.id,
                ...companyQuery,
                year: y,
                ...(workspace === 'front' || workspace === 'back'
                    ? { workspace }
                    : {}),
            }),
            {},
            { preserveState: true },
        );
    };

    const submitSync = (e) => {
        e.preventDefault();
        syncForm.post(
            route('finance.positions.accruals.sync-year', {
                category,
                position: position.id,
            }),
        );
    };

    const submitManual = (e) => {
        e.preventDefault();
        manualForm.post(
            route('finance.positions.accruals.manual', {
                category,
                position: position.id,
            }),
        );
    };

    const submitQuarter = (quarter, form) => {
        form.post(
            route('finance.positions.accruals.post-savings-quarter', {
                category,
                position: position.id,
            }),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Schedule — {categoryLabel}: {position.title}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="finance.positions.show"
                            routeParams={{ category, position: position.id }}
                            query={{
                                year,
                                ...(workspace === 'front' || workspace === 'back'
                                    ? { workspace }
                                    : {}),
                            }}
                        />
                        <Link
                            href={route('finance.positions.edit', {
                                category,
                                position: position.id,
                                ...companyQuery,
                                ...(workspace === 'front' || workspace === 'back'
                                    ? { workspace }
                                    : {}),
                            })}
                            className="text-sm text-indigo-600 underline"
                        >
                            Edit position
                        </Link>
                        <Link
                            href={route('finance.positions.index', {
                                category,
                                ...companyQuery,
                                ...(workspace === 'front' || workspace === 'back'
                                    ? { workspace }
                                    : {}),
                            })}
                            className="text-sm text-gray-600 underline"
                        >
                            Back to list
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Schedule — ${position.title}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    {flash.status && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.status}
                        </div>
                    )}

                    {category === 'loan' &&
                        position.uses_structured_loan &&
                        position.is_loan_pending_approval &&
                        workspace === 'front' && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                <strong>Pending approval.</strong> Proposed
                                account{' '}
                                <span className="font-mono font-semibold">
                                    {position.proposed_account_number}
                                </span>
                                . A back office user must approve before
                                disbursement, repayments, or statements are
                                available.
                            </div>
                        )}

                    {category === 'loan' &&
                        position.uses_structured_loan &&
                        position.is_loan_pending_approval &&
                        workspace === 'back' && (
                            <div className="rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                                <h3 className="text-base font-semibold text-indigo-950">
                                    Loan application (back office)
                                </h3>
                                {position.loan_product && (
                                    <p className="mt-1 text-sm text-indigo-900">
                                        Product{' '}
                                        <span className="font-mono font-medium">
                                            {position.loan_product.product_code}
                                        </span>
                                        {' — '}
                                        {position.loan_product.name}
                                    </p>
                                )}
                                <p className="mt-2 text-sm text-indigo-900">
                                    Proposed account number:{' '}
                                    <span className="font-mono text-base font-bold tabular-nums">
                                        {position.proposed_account_number}
                                    </span>
                                    {position.sanctioned_amount_cents !=
                                        null && (
                                        <>
                                            {' '}
                                            · Sanctioned principal:{' '}
                                            <span className="font-semibold tabular-nums">
                                                {moneyFromCents(
                                                    position.sanctioned_amount_cents,
                                                )}
                                            </span>
                                        </>
                                    )}
                                </p>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        className="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-800"
                                        onClick={() =>
                                            router.post(
                                                route(
                                                    'finance.positions.loan.approve',
                                                    {
                                                        category: 'loan',
                                                        position:
                                                            position.id,
                                                    },
                                                ),
                                                isAdmin && currentCompanyId
                                                    ? {
                                                          company_id:
                                                              currentCompanyId,
                                                      }
                                                    : {},
                                            )
                                        }
                                    >
                                        Approve &amp; activate account
                                    </button>
                                    <button
                                        type="button"
                                        className="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50"
                                        onClick={() => {
                                            if (
                                                !confirm(
                                                    'Reject this loan application?',
                                                )
                                            ) {
                                                return;
                                            }
                                            router.post(
                                                route(
                                                    'finance.positions.loan.reject',
                                                    {
                                                        category: 'loan',
                                                        position:
                                                            position.id,
                                                    },
                                                ),
                                                isAdmin && currentCompanyId
                                                    ? {
                                                          company_id:
                                                              currentCompanyId,
                                                      }
                                                    : {},
                                            );
                                        }}
                                    >
                                        Reject application
                                    </button>
                                </div>
                            </div>
                        )}

                    {category === 'savings' &&
                        position.uses_structured_savings &&
                        position.is_savings_pending_approval &&
                        workspace === 'front' && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                <strong>Pending approval.</strong> Proposed
                                account{' '}
                                <span className="font-mono font-semibold">
                                    {position.proposed_savings_account_number}
                                </span>
                                . A back office user must approve before
                                deposits, withdrawals, or statements are
                                available.
                            </div>
                        )}

                    {category === 'savings' &&
                        position.uses_structured_savings &&
                        position.is_savings_pending_approval &&
                        workspace === 'back' && (
                            <div className="rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                                <h3 className="text-base font-semibold text-indigo-950">
                                    Savings application (back office)
                                </h3>
                                {position.savings_product && (
                                    <p className="mt-1 text-sm text-indigo-900">
                                        Product{' '}
                                        <span className="font-mono font-medium">
                                            {
                                                position.savings_product
                                                    .product_code
                                            }
                                        </span>
                                        {' — '}
                                        {position.savings_product.name}
                                    </p>
                                )}
                                <p className="mt-2 text-sm text-indigo-900">
                                    Proposed account number:{' '}
                                    <span className="font-mono text-base font-bold tabular-nums">
                                        {
                                            position.proposed_savings_account_number
                                        }
                                    </span>
                                </p>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        className="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-800"
                                        onClick={() =>
                                            router.post(
                                                route(
                                                    'finance.positions.savings.approve',
                                                    {
                                                        category: 'savings',
                                                        position:
                                                            position.id,
                                                    },
                                                ),
                                                isAdmin && currentCompanyId
                                                    ? {
                                                          company_id:
                                                              currentCompanyId,
                                                      }
                                                    : {},
                                            )
                                        }
                                    >
                                        Approve &amp; activate account
                                    </button>
                                    <button
                                        type="button"
                                        className="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50"
                                        onClick={() => {
                                            if (
                                                !confirm(
                                                    'Reject this savings application?',
                                                )
                                            ) {
                                                return;
                                            }
                                            router.post(
                                                route(
                                                    'finance.positions.savings.reject',
                                                    {
                                                        category: 'savings',
                                                        position:
                                                            position.id,
                                                    },
                                                ),
                                                isAdmin && currentCompanyId
                                                    ? {
                                                          company_id:
                                                              currentCompanyId,
                                                      }
                                                    : {},
                                            );
                                        }}
                                    >
                                        Reject application
                                    </button>
                                </div>
                            </div>
                        )}

                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow sm:rounded-lg">
                        <div className="flex flex-wrap items-end gap-4">
                            <div>
                                <InputLabel value="Year" />
                                <select
                                    className="mt-1 block rounded-md border-gray-300 shadow-sm"
                                    value={year}
                                    onChange={(e) =>
                                        goYear(Number(e.target.value))
                                    }
                                >
                                    {Array.from({ length: 15 }, (_, i) => year - 7 + i).map(
                                        (y) => (
                                            <option key={y} value={y}>
                                                {y}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>
                            <div className="text-sm text-gray-600">
                                {position.member && (
                                    <>
                                        Member{' '}
                                        <span className="font-semibold text-gray-900">
                                            #{position.member.member_number}{' '}
                                            {position.member.name}
                                        </span>
                                        {' · '}
                                    </>
                                )}
                                Principal:{' '}
                                <span className="font-semibold tabular-nums text-gray-900">
                                    {moneyFromCents(position.principal_cents)}
                                </span>
                                {position.uses_banking_monthly && (
                                    <>
                                        {' '}
                                        · Monthly interest (from rate):{' '}
                                        <span className="font-semibold tabular-nums text-gray-900">
                                            {moneyFromCents(
                                                position.monthly_interest_cents,
                                            )}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>
                        {!canApprove && (
                            <p className="mt-3 text-sm text-amber-800">
                                Entries you post are saved as{' '}
                                <strong>draft</strong> until a company approver
                                approves them; only then do they affect
                                financial statements.
                            </p>
                        )}
                    </div>

                    {accounts.length === 0 && (
                        <p className="text-sm text-amber-800">
                            Add <strong>approved</strong> chart accounts before
                            posting to the ledger.
                        </p>
                    )}

                    {!memberFinanceOk && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                            <p className="font-medium">
                                Approved member required
                            </p>
                            <p className="mt-1 text-amber-900">
                                Link this position to an{' '}
                                <strong>approved</strong> member (member number)
                                before{' '}
                                {position.uses_banking_monthly
                                    ? 'syncing the monthly schedule, posting loan or savings interest,'
                                    : 'recording investment accruals or posting returns'}{' '}
                                so entries appear on the correct individual
                                ledger.
                            </p>
                            <Link
                                href={route('finance.positions.edit', {
                                    category,
                                    position: position.id,
                                    ...companyQuery,
                                })}
                                className="mt-2 inline-block font-medium text-amber-950 underline"
                            >
                                Edit position
                            </Link>
                        </div>
                    )}

                    {position.uses_banking_monthly && (
                        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow sm:rounded-lg">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Monthly schedule (banking-style)
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Generate or refresh twelve unpaid months for{' '}
                                {year}. Posted months are not overwritten.
                            </p>
                            <form
                                onSubmit={submitSync}
                                className="mt-3 flex flex-wrap items-end gap-3"
                            >
                                {isAdmin && (
                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value={
                                            syncForm.data.company_id ||
                                            currentCompanyId
                                        }
                                    />
                                )}
                                <div>
                                    <InputLabel value="Year to sync" />
                                    <TextInput
                                        type="number"
                                        min="2000"
                                        max="2100"
                                        className="mt-1 w-28"
                                        value={syncForm.data.year}
                                        onChange={(e) =>
                                            syncForm.setData(
                                                'year',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <PrimaryButton
                                    disabled={
                                        syncForm.processing || !memberFinanceOk
                                    }
                                >
                                    Sync year
                                </PrimaryButton>
                                <InputError
                                    message={errors.sync ?? errors.member}
                                    className="w-full"
                                />
                            </form>
                        </div>
                    )}

                    {category === 'investment' && (
                        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow sm:rounded-lg">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Manual return by month
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Enter the return amount for a calendar month.
                                Post it to the ledger when paid or recognized.
                            </p>
                            <form
                                onSubmit={submitManual}
                                className="mt-3 flex flex-wrap items-end gap-3"
                            >
                                {isAdmin && (
                                    <input
                                        type="hidden"
                                        name="company_id"
                                        value={
                                            manualForm.data.company_id ||
                                            currentCompanyId
                                        }
                                    />
                                )}
                                <div>
                                    <InputLabel value="Year" />
                                    <TextInput
                                        type="number"
                                        min="2000"
                                        max="2100"
                                        className="mt-1 w-28"
                                        value={manualForm.data.year}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                'year',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Month" />
                                    <select
                                        className="mt-1 block rounded-md border-gray-300"
                                        value={manualForm.data.month}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                'month',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        {MONTHS.map((label, i) => (
                                            <option
                                                key={label}
                                                value={String(i + 1)}
                                            >
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Amount (NPR)" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        className="mt-1 w-32"
                                        value={manualForm.data.amount}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                'amount',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <PrimaryButton
                                    disabled={
                                        manualForm.processing || !memberFinanceOk
                                    }
                                >
                                    Save accrual
                                </PrimaryButton>
                                <InputError
                                    message={
                                        errors.manual ?? errors.amount
                                    }
                                    className="w-full"
                                />
                            </form>
                        </div>
                    )}

                    {category === 'savings' && savings_quarters?.length > 0 && (
                        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow sm:rounded-lg">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Quarter-end transfer
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Post <strong>one</strong> journal for all three
                                unpaid months in a quarter (interest credited
                                quarterly).
                            </p>
                            <div className="mt-4 space-y-6">
                                {savings_quarters.map((q) => (
                                    <QuarterPostForm
                                        key={q.quarter}
                                        quarter={q}
                                        year={year}
                                        accounts={accounts}
                                        isAdmin={isAdmin}
                                        currentCompanyId={currentCompanyId}
                                        onSubmit={submitQuarter}
                                        errors={errors}
                                        today={today}
                                        memberFinanceOk={memberFinanceOk}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Month
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Amount
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Ledger
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Post
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {accruals.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No accruals for this year.{' '}
                                            {position.uses_banking_monthly
                                                ? 'Use “Sync year”.'
                                                : 'Add manual amounts above.'}
                                        </td>
                                    </tr>
                                ) : (
                                    accruals.map((row) => (
                                        <AccrualTableRow
                                            key={row.id}
                                            row={row}
                                            category={category}
                                            positionId={position.id}
                                            accounts={accounts}
                                            isAdmin={isAdmin}
                                            currentCompanyId={currentCompanyId}
                                            companyQuery={companyQuery}
                                            errors={errors}
                                            today={today}
                                            memberFinanceOk={memberFinanceOk}
                                        />
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function QuarterPostForm({
    quarter,
    year,
    accounts,
    isAdmin,
    currentCompanyId,
    onSubmit,
    errors,
    today,
    memberFinanceOk,
}) {
    const form = useForm({
        year: String(year),
        quarter: quarter.quarter,
        transaction_date: today,
        debit_chart_account_id: '',
        credit_chart_account_id: '',
        reference: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            form.setData('company_id', String(currentCompanyId));
        }
    }, [currentCompanyId, isAdmin]);

    useEffect(() => {
        form.setData('year', String(year));
    }, [year]);

    return (
        <div
            className={`rounded-md border p-4 ${
                quarter.ready
                    ? 'border-green-200 bg-green-50/50'
                    : 'border-gray-100 bg-gray-50'
            }`}
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-sm font-medium text-gray-900">
                    Q{quarter.quarter} — unpaid months: {quarter.unpaid_count}{' '}
                    / 3 · total{' '}
                    <span className="tabular-nums">
                        {moneyFromCents(quarter.total_cents)}
                    </span>
                </span>
            </div>
            {quarter.ready ? (
                <form
                    className="mt-3 space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        onSubmit(quarter.quarter, form);
                    }}
                >
                    {isAdmin && (
                        <input
                            type="hidden"
                            name="company_id"
                            value={
                                form.data.company_id || currentCompanyId || ''
                            }
                        />
                    )}
                    <input type="hidden" name="year" value={form.data.year} />
                    <input
                        type="hidden"
                        name="quarter"
                        value={form.data.quarter}
                    />
                    <LedgerFields
                        accounts={accounts}
                        data={form.data}
                        setData={form.setData}
                        errors={errors}
                    />
                    <PrimaryButton
                        disabled={form.processing || !memberFinanceOk}
                        type="submit"
                    >
                        Post quarter to ledger
                    </PrimaryButton>
                    <InputError message={errors.quarter} />
                    <InputError message={errors.amount} />
                    <InputError message={errors.ledger} />
                    <InputError message={errors.member} />
                </form>
            ) : (
                <p className="mt-2 text-xs text-gray-600">
                    Sync the year and ensure all three months in this quarter
                    have unpaid accruals before posting.
                </p>
            )}
        </div>
    );
}

function AccrualTableRow({
    row,
    category,
    positionId,
    accounts,
    isAdmin,
    currentCompanyId,
    companyQuery,
    errors,
    today,
    memberFinanceOk,
}) {
    const form = useForm({
        transaction_date: today,
        debit_chart_account_id: '',
        credit_chart_account_id: '',
        reference: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            form.setData('company_id', String(currentCompanyId));
        }
    }, [currentCompanyId, isAdmin]);

    const isSavingsMonthly = row.kind === 'savings_monthly';
    const financePostNeedsMemberOk =
        (category === 'loan' || category === 'investment') &&
        !memberFinanceOk;
    const canPostRow =
        !row.posted &&
        !isSavingsMonthly &&
        row.amount_cents > 0 &&
        accounts.length > 0 &&
        !financePostNeedsMemberOk;

    const submitPost = (e) => {
        e.preventDefault();
        form.post(
            route('finance.positions.accruals.post-ledger', {
                category,
                position: positionId,
                accrual: row.id,
            }),
        );
    };

    return (
        <tr>
            <td className="px-4 py-3 text-sm text-gray-900">
                {MONTHS[row.accrual_month - 1]} {row.accrual_year}
            </td>
            <td className="px-4 py-3 text-sm text-gray-700">
                {KIND_LABEL[row.kind] ?? row.kind}
            </td>
            <td className="px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                {moneyFromCents(row.amount_cents)}
            </td>
            <td className="px-4 py-3 text-sm">
                {row.posted && row.journal_entry_id ? (
                    <Link
                        href={route('journals.show', {
                            journal: row.journal_entry_id,
                            ...companyQuery,
                        })}
                        className="text-indigo-600 hover:text-indigo-900"
                    >
                        Journal #{row.journal_entry_id}
                    </Link>
                ) : (
                    <span className="text-gray-500">—</span>
                )}
            </td>
            <td className="px-4 py-3 text-sm">
                {isSavingsMonthly && category === 'savings' && (
                    <span className="text-gray-500">
                        Use quarter posting above
                    </span>
                )}
                {canPostRow && (
                    <form onSubmit={submitPost} className="space-y-2">
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={
                                    form.data.company_id ||
                                    currentCompanyId ||
                                    ''
                                }
                            />
                        )}
                        <LedgerFields
                            accounts={accounts}
                            data={form.data}
                            setData={form.setData}
                            errors={errors}
                        />
                        <PrimaryButton
                            type="submit"
                            disabled={form.processing}
                            className="text-xs"
                        >
                            Post this month
                        </PrimaryButton>
                        <InputError message={errors.posted} />
                        <InputError message={errors.kind} />
                        <InputError message={errors.amount} />
                        <InputError message={errors.ledger} />
                        <InputError message={errors.member} />
                    </form>
                )}
                {financePostNeedsMemberOk &&
                    !row.posted &&
                    !isSavingsMonthly &&
                    row.amount_cents > 0 && (
                        <span className="text-xs text-amber-800">
                            Set approved member on Edit to post.
                        </span>
                    )}
                {!canPostRow &&
                    !isSavingsMonthly &&
                    !row.posted &&
                    row.amount_cents <= 0 && (
                        <span className="text-gray-400">—</span>
                    )}
            </td>
        </tr>
    );
}
