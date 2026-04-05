import LoanSavingsProductModal from '@/Components/LoanSavingsProductModal';
import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function AccountEntry({
    category: initialCategory,
    account_number_query = '',
    lookup_attempted = false,
    resolved = null,
    modal_chart_accounts = [],
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const { data, setData, get, processing, errors } = useForm({
        category: initialCategory || 'savings',
        account_number: account_number_query || '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        setData((prev) => ({
            ...prev,
            category: initialCategory || 'savings',
            account_number: account_number_query || '',
            company_id: isAdmin ? String(currentCompanyId ?? '') : prev.company_id,
        }));
    }, [initialCategory, account_number_query, currentCompanyId, isAdmin, setData]);

    const [sheetOpen, setSheetOpen] = useState(false);

    const submitLookup = (e) => {
        e.preventDefault();
        get(route('finance.account-entry'), { preserveState: true });
    };

    const categoryLabel =
        data.category === 'loan' ? 'Loan' : 'Savings';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Account number entry
                    </h2>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="finance.account-entry"
                            routeParams={{}}
                            query={{
                                category: data.category,
                                ...(data.account_number
                                    ? { account_number: data.account_number }
                                    : {}),
                            }}
                        />
                    )}
                </div>
            }
        >
            <Head title="Account number entry" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-6 text-sm text-gray-600">
                        Enter a{' '}
                        <strong>loan or savings account number</strong> to
                        record deposits, withdrawals, adjustments, or view /
                        print a statement — same actions as on the finance
                        list. Numbers are shown on each product row and on
                        printed statements.
                    </p>

                    <form
                        onSubmit={submitLookup}
                        className="space-y-5 bg-white p-6 shadow sm:rounded-lg"
                    >
                        <div>
                            <InputLabel htmlFor="category" value="Product type" />
                            <select
                                id="category"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.category}
                                onChange={(e) =>
                                    setData('category', e.target.value)
                                }
                            >
                                <option value="savings">Savings</option>
                                <option value="loan">Loan</option>
                            </select>
                            <InputError
                                message={errors.category}
                                className="mt-2"
                            />
                        </div>
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
                                    setData('account_number', e.target.value)
                                }
                                placeholder="e.g. SV-12 or LN-5"
                                autoComplete="off"
                            />
                            <InputError
                                message={errors.account_number}
                                className="mt-2"
                            />
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <PrimaryButton disabled={processing}>
                                Look up account
                            </PrimaryButton>
                            <Link
                                href={route('finance.positions.index', {
                                    category: data.category,
                                    ...companyQuery,
                                })}
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                Open {categoryLabel} list
                            </Link>
                        </div>
                    </form>

                    {lookup_attempted && !resolved && (
                        <div
                            className="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                            role="alert"
                        >
                            No {categoryLabel.toLowerCase()} account found for
                            that number in the selected company. Check the
                            product type and number, or open the list to verify.
                        </div>
                    )}

                    {resolved && (
                        <div className="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 className="text-base font-semibold text-gray-900">
                                {resolved.title}
                            </h3>
                            <p className="mt-1 font-mono text-sm tabular-nums text-gray-600">
                                Account{' '}
                                <span className="font-semibold text-gray-900">
                                    {resolved.account_number}
                                </span>
                            </p>
                            {resolved.member_name && (
                                <p className="mt-2 text-sm text-gray-700">
                                    Member{' '}
                                    {resolved.member_number != null && (
                                        <span className="font-medium tabular-nums">
                                            #{resolved.member_number}{' '}
                                        </span>
                                    )}
                                    {resolved.member_name}
                                </p>
                            )}
                            <p className="mt-2 text-sm text-gray-700">
                                Balance{' '}
                                <span className="font-semibold tabular-nums">
                                    {moneyFromCents(resolved.principal_cents)}
                                </span>
                            </p>
                            <button
                                type="button"
                                onClick={() => setSheetOpen(true)}
                                className="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                Deposit, withdraw, statement, adjustment…
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <LoanSavingsProductModal
                open={sheetOpen}
                onClose={() => setSheetOpen(false)}
                category={data.category}
                row={resolved}
                companyQuery={companyQuery}
                isAdmin={isAdmin}
                currentCompanyId={currentCompanyId}
                chartAccounts={modal_chart_accounts}
                onAfterMovementSuccess={() => router.reload()}
            />
        </AuthenticatedLayout>
    );
}
