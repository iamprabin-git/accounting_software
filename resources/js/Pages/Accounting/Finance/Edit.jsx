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

export default function Edit({
    category,
    categoryLabel,
    position,
    approved_members = [],
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};
    const [sampleDays, setSampleDays] = useState('30');

    const { data, setData, put, processing, errors } = useForm({
        title: position.title,
        principal: String(position.principal),
        annual_interest_rate_percent: String(
            position.annual_interest_rate_percent,
        ),
        start_date: position.start_date || '',
        notes: position.notes || '',
        member_id: position.member_id ? String(position.member_id) : '',
        account_number: position.account_number || '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const isInvestment = category === 'investment';
    const needsMember = ['loan', 'savings', 'investment'].includes(category);
    const isLoanOrSavings =
        category === 'loan' || category === 'savings';

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
        put(
            route('finance.positions.update', {
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
                        Edit — {categoryLabel}
                    </h2>
                    <Link
                        href={route('finance.positions.show', {
                            category,
                            position: position.id,
                            ...companyQuery,
                        })}
                        className="text-sm text-indigo-600 underline"
                    >
                        Schedule &amp; ledger posting
                    </Link>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="finance.positions.edit"
                        routeParams={{ category, position: position.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`Edit ${categoryLabel}`} />

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
                                        No approved members available. Use{' '}
                                        <Link
                                            href={route(
                                                'members.index',
                                                companyQuery,
                                            )}
                                            className="font-medium underline"
                                        >
                                            Members
                                        </Link>{' '}
                                        to register and approve.
                                    </p>
                                )}
                                <InputError
                                    message={errors.member_id}
                                    className="mt-2"
                                />
                            </div>
                        )}
                        {isLoanOrSavings && (
                            <div>
                                <InputLabel
                                    htmlFor="account_number"
                                    value="Account number"
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
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Unique per company. Clear the field to
                                    reassign the default (LN- or SV- plus
                                    product ID).
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
                                value="Principal / balance (NPR)"
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
                            />
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

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Update
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={route('finance.positions.index', {
                                    category,
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
