import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    annualInterestCents,
    dollarsToCents,
    monthlyInterestCents,
    simpleInterestCents,
} from '@/utils/interest';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function Create({
    category,
    categoryLabel,
    workspace = 'full',
    approved_members = [],
    loan_products = [],
    savings_products = [],
    initial_form = {},
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const [sampleDays, setSampleDays] = useState('30');

    const { data, setData, post, processing, errors } = useForm({
        title: initial_form.title || '',
        principal: '0',
        annual_interest_rate_percent:
            initial_form.annual_interest_rate_percent || '0',
        start_date: initial_form.start_date || '',
        notes: '',
        member_id: initial_form.member_id || '',
        account_number: '',
        loan_product_id: initial_form.loan_product_id || '',
        savings_product_id: initial_form.savings_product_id || '',
        sanctioned_amount: '',
        workspace:
            workspace === 'front' || workspace === 'back' ? workspace : '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    useEffect(() => {
        if (initial_form.title) setData('title', initial_form.title);
        if (initial_form.member_id) setData('member_id', String(initial_form.member_id));
        if (initial_form.loan_product_id)
            setData('loan_product_id', String(initial_form.loan_product_id));
        if (initial_form.savings_product_id)
            setData('savings_product_id', String(initial_form.savings_product_id));
        if (initial_form.annual_interest_rate_percent)
            setData(
                'annual_interest_rate_percent',
                String(initial_form.annual_interest_rate_percent),
            );
        if (initial_form.start_date) setData('start_date', initial_form.start_date);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- hydrate once from server prefill
    }, []);

    useEffect(() => {
        if (category !== 'loan' || !data.loan_product_id) {
            return;
        }
        const p = loan_products.find(
            (x) => String(x.id) === String(data.loan_product_id),
        );
        if (p) {
            setData(
                'annual_interest_rate_percent',
                p.default_annual_interest_rate_percent,
            );
            setData('principal', '0');
        }
    }, [data.loan_product_id, category, loan_products, setData]);

    useEffect(() => {
        if (category !== 'savings' || !data.savings_product_id) {
            return;
        }
        const p = savings_products.find(
            (x) => String(x.id) === String(data.savings_product_id),
        );
        if (p) {
            setData(
                'annual_interest_rate_percent',
                p.default_annual_interest_rate_percent,
            );
            setData('principal', '0');
        }
    }, [data.savings_product_id, category, savings_products, setData]);

    const isInvestment = category === 'investment';
    const needsMember = ['loan', 'savings', 'investment'].includes(category);
    const isLoanOrSavings =
        category === 'loan' || category === 'savings';
    const structuredLoanApplication =
        category === 'loan' && Boolean(data.loan_product_id);
    const structuredSavingsApplication =
        category === 'savings' && Boolean(data.savings_product_id);

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};
    const workspaceQuery =
        workspace === 'front' || workspace === 'back'
            ? { workspace }
            : {};

    const preview = useMemo(() => {
        const pCents = dollarsToCents(data.principal);
        const rate = parseFloat(data.annual_interest_rate_percent) || 0;
        const days = parseInt(sampleDays, 10) || 0;
        return {
            annual: annualInterestCents(pCents, rate),
            monthly: monthlyInterestCents(pCents, rate),
            period: simpleInterestCents(pCents, rate, days),
        };
    }, [data.principal, data.annual_interest_rate_percent, sampleDays]);

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.positions.store', { category }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        New — {categoryLabel}
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="finance.positions.create"
                        routeParams={{ category }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`New ${categoryLabel}`} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <Card>
                        <CardContent className="p-6 sm:p-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id || currentCompanyId}
                            />
                        )}
                        {(workspace === 'front' || workspace === 'back') && (
                            <input
                                type="hidden"
                                name="workspace"
                                value={workspace}
                            />
                        )}
                        <div>
                            <InputLabel htmlFor="title" value="Title" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>
                        {needsMember && (
                            <div>
                                <InputLabel
                                    htmlFor="member_id"
                                    value="Approved member"
                                />
                                <select
                                    id="member_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.member_id}
                                    onChange={(e) =>
                                        setData('member_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">
                                        Select approved member…
                                    </option>
                                    {approved_members.map((m) => (
                                        <option key={m.id} value={String(m.id)}>
                                            {m.label}
                                        </option>
                                    ))}
                                </select>
                                {approved_members.length === 0 && (
                                    <p className="mt-2 text-sm text-amber-800">
                                        No approved members for this company.
                                        Register one under{' '}
                                        <Link
                                            href={route(
                                                'members.create',
                                                companyQuery,
                                            )}
                                            className="font-medium underline"
                                        >
                                            Members
                                        </Link>{' '}
                                        and have a company approver approve it.
                                    </p>
                                )}
                                <InputError
                                    message={errors.member_id}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        {category === 'loan' && loan_products.length > 0 && (
                            <div>
                                <InputLabel
                                    htmlFor="loan_product_id"
                                    value="Loan product"
                                />
                                <select
                                    id="loan_product_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.loan_product_id}
                                    onChange={(e) =>
                                        setData(
                                            'loan_product_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Ad-hoc loan (no product / immediate
                                        account LN-…)
                                    </option>
                                    {loan_products.map((p) => (
                                        <option
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.product_code} — {p.name}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-xs text-gray-600">
                                    Choose a product for the standard flow:
                                    member applies → proposed account like{' '}
                                    <span className="font-mono">11-0001</span>{' '}
                                    → back office approves → disburse with
                                    journal. Leave empty for a simple one-step
                                    loan.
                                </p>
                                <InputError
                                    message={errors.loan_product_id}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        {category === 'savings' && savings_products.length > 0 && (
                            <div>
                                <InputLabel
                                    htmlFor="savings_product_id"
                                    value="Savings product"
                                />
                                <select
                                    id="savings_product_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.savings_product_id}
                                    onChange={(e) =>
                                        setData(
                                            'savings_product_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Ad-hoc savings (no product / immediate
                                        account SV-…)
                                    </option>
                                    {savings_products.map((p) => (
                                        <option
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.product_code} — {p.name}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-xs text-gray-600">
                                    Choose a product for the standard flow:
                                    member opens account → proposed number like{' '}
                                    <span className="font-mono">01-0001</span>{' '}
                                    → back office approves → deposits and
                                    withdrawals post with journal entries.
                                    Leave empty for a simple one-step savings
                                    account.
                                </p>
                                <InputError
                                    message={errors.savings_product_id}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        {structuredLoanApplication && (
                            <div>
                                <InputLabel
                                    htmlFor="sanctioned_amount"
                                    value="Sanctioned principal (optional cap)"
                                />
                                <TextInput
                                    id="sanctioned_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.sanctioned_amount}
                                    onChange={(e) =>
                                        setData(
                                            'sanctioned_amount',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Max total disbursed (optional)"
                                />
                                <InputError
                                    message={errors.sanctioned_amount}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        {isLoanOrSavings &&
                            !structuredLoanApplication &&
                            !structuredSavingsApplication && (
                            <div>
                                <InputLabel
                                    htmlFor="account_number"
                                    value="Account number (optional)"
                                />
                                <TextInput
                                    id="account_number"
                                    className="mt-1 block w-full font-mono tabular-nums"
                                    value={data.account_number}
                                    onChange={(e) =>
                                        setData(
                                            'account_number',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Leave blank to auto-assign (e.g. SV-42)"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Unique per company. If empty, a number is
                                    assigned from the product ID.
                                </p>
                                <InputError
                                    message={errors.account_number}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        <div>
                            <InputLabel
                                htmlFor="principal"
                                value={
                                    structuredLoanApplication ||
                                    structuredSavingsApplication
                                        ? 'Principal at opening (NPR)'
                                        : 'Principal / balance (NPR)'
                                }
                            />
                            <TextInput
                                id="principal"
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 block w-full"
                                value={data.principal}
                                onChange={(e) =>
                                    setData('principal', e.target.value)
                                }
                                required
                                readOnly={
                                    structuredLoanApplication ||
                                    structuredSavingsApplication
                                }
                                disabled={
                                    structuredLoanApplication ||
                                    structuredSavingsApplication
                                }
                            />
                            {structuredLoanApplication && (
                                <p className="mt-1 text-xs text-gray-600">
                                    Stays at zero until back office approves the
                                    account, then record disbursements from
                                    product actions (with journal).
                                </p>
                            )}
                            {structuredSavingsApplication && (
                                <p className="mt-1 text-xs text-gray-600">
                                    Stays at zero until back office approves the
                                    account, then record deposits from product
                                    actions (with journal).
                                </p>
                            )}
                            <InputError
                                message={errors.principal}
                                className="mt-2"
                            />
                        </div>
                        {!isInvestment && (
                            <div>
                                <InputLabel
                                    htmlFor="annual_interest_rate_percent"
                                    value="Annual interest rate %"
                                />
                                <TextInput
                                    id="annual_interest_rate_percent"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    max="100"
                                    className="mt-1 block w-full"
                                    value={data.annual_interest_rate_percent}
                                    onChange={(e) =>
                                        setData(
                                            'annual_interest_rate_percent',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={
                                        errors.annual_interest_rate_percent
                                    }
                                    className="mt-2"
                                />
                            </div>
                        )}
                        <div>
                            <InputLabel
                                htmlFor="start_date"
                                value="Start date (optional)"
                            />
                            <TextInput
                                id="start_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData('start_date', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.start_date}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="notes" value="Notes" />
                            <textarea
                                id="notes"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError message={errors.notes} className="mt-2" />
                        </div>

                        {!isInvestment && (
                            <div className="rounded-lg border border-indigo-100 bg-indigo-50/80 p-4 text-sm">
                                <p className="font-medium text-indigo-950">
                                    Calculated interest (preview)
                                </p>
                                <ul className="mt-2 space-y-1 text-indigo-900">
                                    <li>
                                        Per year:{' '}
                                        <span className="font-semibold tabular-nums">
                                            {moneyFromCents(preview.annual)}
                                        </span>
                                    </li>
                                    <li>
                                        Per month (÷12):{' '}
                                        <span className="font-semibold tabular-nums">
                                            {moneyFromCents(preview.monthly)}
                                        </span>
                                    </li>
                                    <li className="flex flex-wrap items-center gap-2 pt-1">
                                        <span>For</span>
                                        <input
                                            type="number"
                                            min="1"
                                            max="3650"
                                            className="w-20 rounded border border-indigo-200 px-2 py-1 text-sm"
                                            value={sampleDays}
                                            onChange={(e) =>
                                                setSampleDays(e.target.value)
                                            }
                                        />
                                        <span>days:</span>
                                        <span className="font-semibold tabular-nums">
                                            {moneyFromCents(preview.period)}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        )}
                        {isInvestment && (
                            <p className="text-sm text-gray-600">
                                After saving, open the{' '}
                                <strong>Schedule</strong> page to enter returns
                                by month and post them to the ledger.
                            </p>
                        )}

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={route('finance.positions.index', {
                                    category,
                                    ...workspaceQuery,
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                            >
                                Cancel
                            </Link>
                            </Button>
                        </div>
                    </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
