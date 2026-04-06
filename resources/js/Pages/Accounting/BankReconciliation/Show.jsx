import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function money(cents) {
    if (cents === null || cents === undefined) {
        return '—';
    }
    const n = Number(cents) / 100;
    return n.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export default function Show({
    batch,
    lines,
    unmatchedGlPreview,
    companies,
    currentCompanyId,
    bankFeedProviders,
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const flashStatus = page.props.flash?.status;
    const flashError = page.props.flash?.error;
    const balanceWarning = page.props.flash?.balance_warning;
    const matchError = page.props.errors?.match;
    const isAdmin = user.role === 'admin';
    const q = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const matched = lines.filter((l) => l.matched).length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800">
                            Reconcile ·{' '}
                            {batch.name || `Import #${batch.id}`}
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            {batch.chart_account
                                ? `${batch.chart_account.code} ${batch.chart_account.name}`
                                : ''}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {isAdmin ? (
                            <CompanyPicker
                                companies={companies}
                                currentCompanyId={currentCompanyId}
                                routeName="bank-reconciliation.show"
                                routeParams={{ batch: batch.id }}
                                query={{}}
                            />
                        ) : null}
                        <Link
                            href={route('bank-reconciliation.index', q)}
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            All imports
                        </Link>
                        <button
                            type="button"
                            onClick={() =>
                                router.post(
                                    route(
                                        'bank-reconciliation.auto-match',
                                        { batch: batch.id, ...q },
                                    ),
                                )
                            }
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Auto-match
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Bank reconciliation" />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                    {flashStatus ? (
                        <div className="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-900">
                            {flashStatus}
                        </div>
                    ) : null}
                    {flashError ? (
                        <div className="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-900">
                            {flashError}
                        </div>
                    ) : null}
                    {balanceWarning ? (
                        <div className="rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-950">
                            {balanceWarning}
                        </div>
                    ) : null}
                    {matchError ? (
                        <div className="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-900">
                            {matchError}
                        </div>
                    ) : null}

                    {(batch.statement_opening_balance_cents != null ||
                        batch.statement_closing_balance_cents != null) && (
                        <div className="rounded border border-gray-200 bg-white p-4 text-sm shadow-sm">
                            <h3 className="font-semibold text-gray-900">
                                Statement balances
                            </h3>
                            <dl className="mt-2 grid gap-1 sm:grid-cols-2">
                                <div>
                                    <dt className="text-gray-500">
                                        Opening (imported)
                                    </dt>
                                    <dd className="font-mono">
                                        {money(
                                            batch.statement_opening_balance_cents,
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">
                                        Sum of statement lines
                                    </dt>
                                    <dd className="font-mono">
                                        {money(batch.sum_statement_lines_cents)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">
                                        Expected closing
                                    </dt>
                                    <dd className="font-mono">
                                        {money(batch.expected_closing_cents)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">
                                        Closing (imported)
                                    </dt>
                                    <dd className="font-mono">
                                        {money(
                                            batch.statement_closing_balance_cents,
                                        )}
                                    </dd>
                                </div>
                            </dl>
                            {batch.balance_variance_cents != null &&
                            batch.balance_variance_cents !== 0 ? (
                                <p className="mt-2 text-amber-800">
                                    Variance:{' '}
                                    <span className="font-mono font-semibold">
                                        {money(batch.balance_variance_cents)}
                                    </span>{' '}
                                    (expected closing minus entered closing)
                                </p>
                            ) : batch.balance_variance_cents === 0 ? (
                                <p className="mt-2 text-green-800">
                                    Opening + lines = closing (within this
                                    import).
                                </p>
                            ) : null}
                        </div>
                    )}

                    {bankFeedProviders?.length ? (
                        <div className="rounded border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-700">
                            <p className="font-medium text-gray-900">
                                Bank feed APIs
                            </p>
                            <p className="mt-1 text-xs text-gray-600">
                                Providers are configured in{' '}
                                <code className="rounded bg-white px-1">
                                    config/bank_feeds.php
                                </code>{' '}
                                and <code className="rounded bg-white px-1">.env</code>.
                                Live pull is not wired yet; use CSV import.
                            </p>
                            <ul className="mt-2 space-y-1 text-xs">
                                {bankFeedProviders.map((p) => (
                                    <li key={p.key}>
                                        {p.label}:{' '}
                                        {p.configured
                                            ? 'credentials present (integration pending)'
                                            : p.enabled
                                              ? 'enabled — add API keys'
                                              : 'off'}
                                    </li>
                                ))}
                            </ul>
                            <button
                                type="button"
                                onClick={() =>
                                    router.post(
                                        route('bank-reconciliation.fetch-feed'),
                                    )
                                }
                                className="mt-2 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs hover:bg-gray-100"
                            >
                                Check feed status
                            </button>
                        </div>
                    ) : null}

                    <p className="text-sm text-gray-600">
                        <strong>{matched}</strong> of{' '}
                        <strong>{lines.length}</strong> statement lines fully
                        matched. You can match <strong>several journal lines</strong>{' '}
                        to one statement line when their nets sum to the
                        statement amount.
                    </p>

                    <div className="overflow-x-auto rounded border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">
                                        Statement date
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        Amount
                                    </th>
                                    <th className="px-3 py-2 text-right">
                                        Matched / Remaining
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        Description
                                    </th>
                                    <th className="px-3 py-2 text-left">
                                        Match
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {lines.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            {row.transaction_date}
                                        </td>
                                        <td className="px-3 py-2 text-right font-mono tabular-nums">
                                            {money(row.amount_cents)}
                                        </td>
                                        <td className="px-3 py-2 text-right text-xs font-mono text-gray-600">
                                            <div>
                                                M: {money(row.matched_sum_cents)}
                                            </div>
                                            <div>
                                                R: {money(row.remaining_cents)}
                                            </div>
                                        </td>
                                        <td className="px-3 py-2 text-gray-700">
                                            <div>{row.description || '—'}</div>
                                            {row.external_reference ? (
                                                <div className="text-xs text-gray-500">
                                                    Ref: {row.external_reference}
                                                </div>
                                            ) : null}
                                        </td>
                                        <td className="px-3 py-2 align-top">
                                            {row.journal_matches?.length ? (
                                                <div className="space-y-2">
                                                    {row.journal_matches.map(
                                                        (jm) => (
                                                            <div
                                                                key={
                                                                    jm.match_id
                                                                }
                                                                className="flex flex-wrap items-center gap-2 text-xs"
                                                            >
                                                                <span className="text-green-800">
                                                                    JE #
                                                                    {
                                                                        jm.journal_entry_id
                                                                    }
                                                                    {jm.posted_number !=
                                                                    null
                                                                        ? ` · #${jm.posted_number}`
                                                                        : ''}{' '}
                                                                    (
                                                                    {money(
                                                                        jm.net_cents,
                                                                    )}
                                                                    )
                                                                </span>
                                                                <Link
                                                                    href={route(
                                                                        'journals.show',
                                                                        {
                                                                            journal:
                                                                                jm.journal_entry_id,
                                                                            ...q,
                                                                        },
                                                                    )}
                                                                    className="text-indigo-600 hover:text-indigo-800"
                                                                >
                                                                    Open
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        router.post(
                                                                            route(
                                                                                'bank-reconciliation.match.remove',
                                                                                {
                                                                                    batch: batch.id,
                                                                                    match: jm.match_id,
                                                                                    ...q,
                                                                                },
                                                                            ),
                                                                        )
                                                                    }
                                                                    className="text-red-700 hover:underline"
                                                                >
                                                                    Remove
                                                                </button>
                                                            </div>
                                                        ),
                                                    )}
                                                    <div className="flex flex-wrap gap-2 border-t border-gray-100 pt-2">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                router.post(
                                                                    route(
                                                                        'bank-reconciliation.unmatch',
                                                                        {
                                                                            batch: batch.id,
                                                                            statementLine:
                                                                                row.id,
                                                                            ...q,
                                                                        },
                                                                    ),
                                                                )
                                                            }
                                                            className="text-xs text-red-700 hover:underline"
                                                        >
                                                            Clear all matches
                                                        </button>
                                                    </div>
                                                </div>
                                            ) : null}
                                            {!row.matched ||
                                            row.remaining_cents !== 0 ? (
                                                <div className="space-y-2">
                                                    {row.suggestions?.length ? (
                                                        <div className="flex flex-col gap-1">
                                                            <span className="text-[10px] uppercase tracking-wide text-gray-500">
                                                                Suggestions
                                                            </span>
                                                            {row.suggestions.map(
                                                                (s) => (
                                                                    <button
                                                                        key={
                                                                            s.id
                                                                        }
                                                                        type="button"
                                                                        onClick={() =>
                                                                            router.post(
                                                                                route(
                                                                                    'bank-reconciliation.match',
                                                                                    {
                                                                                        batch: batch.id,
                                                                                        ...q,
                                                                                    },
                                                                                ),
                                                                                {
                                                                                    bank_statement_line_id:
                                                                                        row.id,
                                                                                    journal_line_id:
                                                                                        s.id,
                                                                                },
                                                                            )
                                                                        }
                                                                        className="rounded border border-gray-200 px-2 py-1 text-left text-xs hover:bg-gray-50"
                                                                    >
                                                                        JE #
                                                                        {
                                                                            s.journal_entry_id
                                                                        }{' '}
                                                                        ·{' '}
                                                                        {
                                                                            s.transaction_date
                                                                        }{' '}
                                                                        ·{' '}
                                                                        {money(
                                                                            s.net_cents,
                                                                        )}
                                                                    </button>
                                                                ),
                                                            )}
                                                        </div>
                                                    ) : !row.journal_matches
                                                          ?.length ? (
                                                        <span className="text-xs text-gray-500">
                                                            No suggestions (check
                                                            remaining amount and
                                                            sign).
                                                        </span>
                                                    ) : null}
                                                </div>
                                            ) : null}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {unmatchedGlPreview?.length ? (
                        <div className="rounded border border-gray-200 bg-white p-4 shadow-sm">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Unmatched GL lines (preview, up to 100)
                            </h3>
                            <p className="mt-1 text-xs text-gray-600">
                                Approved journal lines on this account not yet
                                linked to any statement import.
                            </p>
                            <ul className="mt-3 max-h-48 space-y-1 overflow-y-auto font-mono text-xs text-gray-700">
                                {unmatchedGlPreview.map((g) => (
                                    <li key={g.id}>
                                        JE #{g.journal_entry_id}{' '}
                                        {g.posted_number != null
                                            ? `(#${g.posted_number}) `
                                            : ''}
                                        · {g.transaction_date} ·{' '}
                                        {money(g.net_cents)}
                                        {g.reference
                                            ? ` · ${g.reference}`
                                            : ''}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
