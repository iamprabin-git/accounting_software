import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';

const CATEGORY_LABEL = {
    all: 'All finance types',
    loan: 'Loans',
    savings: 'Savings',
    investment: 'Investments',
};

export default function Ledger({
    member,
    category,
    entries,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const setCategory = (cat) => {
        router.get(
            route('members.ledger', { member: member.id }),
            { ...companyQuery, category: cat === 'all' ? undefined : cat },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Member ledger
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            #{member.member_number} — {member.name}
                            {member.status !== 'approved' && (
                                <span className="ms-2 text-amber-700">
                                    ({member.status})
                                </span>
                            )}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="members.ledger"
                            routeParams={{ member: member.id }}
                            query={{ category: category === 'all' ? undefined : category }}
                        />
                        <Link
                            href={route('members.index', companyQuery)}
                            className="text-sm text-gray-600 underline"
                        >
                            Back to members
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Ledger — ${member.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <p className="text-sm text-gray-600">
                        Journal entries posted from finance (loan, savings, or
                        investment) for this member number. General journals
                        without a member link do not appear here.
                    </p>

                    <div className="flex flex-wrap gap-2">
                        {Object.entries(CATEGORY_LABEL).map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => setCategory(key)}
                                className={`rounded-md px-3 py-2 text-sm font-medium ${
                                    category === key
                                        ? 'bg-gray-800 text-white'
                                        : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                }`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Date
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Memo
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Lines
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Journal
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {entries.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No finance journal entries for this
                                            filter.
                                        </td>
                                    </tr>
                                ) : (
                                    entries.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                                {row.transaction_date}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {row.finance_category
                                                    ? CATEGORY_LABEL[
                                                          row.finance_category
                                                      ] ?? row.finance_category
                                                    : '—'}
                                            </td>
                                            <td className="max-w-xs px-4 py-3 text-sm text-gray-800">
                                                <span className="line-clamp-3">
                                                    {row.memo || '—'}
                                                </span>
                                                {row.reference && (
                                                    <span className="mt-1 block text-xs text-gray-500">
                                                        Ref: {row.reference}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700">
                                                <ul className="space-y-1">
                                                    {row.lines.map((line, i) => (
                                                        <li
                                                            key={i}
                                                            className="text-xs"
                                                        >
                                                            {line.chart_label}
                                                            {line.debit_cents >
                                                                0 && (
                                                                <span className="ms-1 tabular-nums text-gray-900">
                                                                    Dr{' '}
                                                                    {moneyFromCents(
                                                                        line.debit_cents,
                                                                    )}
                                                                </span>
                                                            )}
                                                            {line.credit_cents >
                                                                0 && (
                                                                <span className="ms-1 tabular-nums text-gray-900">
                                                                    Cr{' '}
                                                                    {moneyFromCents(
                                                                        line.credit_cents,
                                                                    )}
                                                                </span>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route('journals.show', {
                                                        journal: row.id,
                                                        ...companyQuery,
                                                    })}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    #{row.id}
                                                </Link>
                                                <span className="ms-2 text-xs text-gray-500">
                                                    {row.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {entries.links?.length > 3 && (
                        <div className="flex flex-wrap gap-1">
                            {entries.links.map((link, i) =>
                                link.url ? (
                                    <button
                                        key={i}
                                        type="button"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-gray-800 text-white'
                                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                        }`}
                                        onClick={() =>
                                            router.get(link.url, {}, {
                                                preserveState: true,
                                            })
                                        }
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className="px-3 py-1 text-sm text-gray-400"
                                    />
                                ),
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
