import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({
    logs,
    filters,
    companies,
    currentCompanyId,
    integrity,
    last_verification,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const query = {
        ...(filters?.action ? { action: filters.action } : {}),
        ...(filters?.from_date ? { from_date: filters.from_date } : {}),
        ...(filters?.to_date ? { to_date: filters.to_date } : {}),
        ...(filters?.journal_entry_id
            ? { journal_entry_id: filters.journal_entry_id }
            : {}),
    };

    const withCompany = (params = {}) =>
        isAdmin && currentCompanyId
            ? { ...params, company_id: currentCompanyId }
            : params;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Audit trail
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="audit-trail.index"
                            routeParams={{}}
                            query={query}
                        />
                        <Link
                            href={route(
                                'audit-trail.export.csv',
                                withCompany(query),
                            )}
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Export CSV
                        </Link>
                        <Link
                            href={route(
                                'audit-trail.export.print',
                                withCompany(query),
                            )}
                            target="_blank"
                            className="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Print / Save PDF
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Audit trail" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <form
                        className="grid gap-3 rounded bg-white p-4 shadow sm:grid-cols-5"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const fd = new FormData(e.currentTarget);
                            const next = {
                                action: (fd.get('action') || '').toString(),
                                from_date: (fd.get('from_date') || '').toString(),
                                to_date: (fd.get('to_date') || '').toString(),
                                journal_entry_id: (
                                    fd.get('journal_entry_id') || ''
                                ).toString(),
                            };
                            router.get(
                                route('audit-trail.index', withCompany({})),
                                Object.fromEntries(
                                    Object.entries(next).filter(
                                        ([, v]) => v !== '',
                                    ),
                                ),
                            );
                        }}
                    >
                        <input
                            name="action"
                            defaultValue={filters?.action ?? ''}
                            placeholder="Action"
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <input
                            name="journal_entry_id"
                            defaultValue={filters?.journal_entry_id ?? ''}
                            placeholder="Journal ID"
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <input
                            type="date"
                            name="from_date"
                            defaultValue={filters?.from_date ?? ''}
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <input
                            type="date"
                            name="to_date"
                            defaultValue={filters?.to_date ?? ''}
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <button className="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">
                            Apply filters
                        </button>
                    </form>

                    <div
                        className={`rounded border p-4 ${
                            integrity?.valid
                                ? 'border-green-200 bg-green-50'
                                : 'border-red-200 bg-red-50'
                        }`}
                    >
                        <p className="text-sm font-semibold text-gray-900">
                            Audit integrity verification
                        </p>
                        <p className="mt-1 text-sm text-gray-700">
                            Chain status:{' '}
                            <span className="font-medium">
                                {integrity?.valid ? 'Valid' : 'Broken'}
                            </span>{' '}
                            · Checked events: {integrity?.checked_count ?? 0}
                        </p>
                        {!integrity?.valid ? (
                            <p className="mt-1 text-sm text-red-800">
                                First broken event ID:{' '}
                                {integrity?.first_broken_event_id ?? '—'} (
                                {integrity?.first_broken_reason ?? 'unknown'})
                            </p>
                        ) : null}
                        <div className="mt-2 text-xs text-gray-600">
                            Last verification event:{' '}
                            {last_verification
                                ? `${last_verification.action} @ ${last_verification.created_at ?? '—'}`
                                : 'none'}
                        </div>
                        <div className="mt-2">
                            <button
                                type="button"
                                className="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700"
                                onClick={() =>
                                    router.post(
                                        route(
                                            'audit-trail.verify-now',
                                            withCompany({}),
                                        ),
                                    )
                                }
                            >
                                Verify now
                            </button>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">When</th>
                                    <th className="px-3 py-2 text-left">Action</th>
                                    <th className="px-3 py-2 text-left">Actor</th>
                                    <th className="px-3 py-2 text-left">Journal</th>
                                    <th className="px-3 py-2 text-left">IP</th>
                                    <th className="px-3 py-2 text-left">Hash</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {logs.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-3 py-2">
                                            {row.created_at}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.action}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.actor_name}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.journal_entry_id ? (
                                                <Link
                                                    className="text-indigo-600 hover:text-indigo-800"
                                                    href={route(
                                                        'journals.show',
                                                        withCompany({
                                                            journal: row.journal_entry_id,
                                                        }),
                                                    )}
                                                >
                                                    #{row.journal_entry_id}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.actor_ip || '—'}
                                        </td>
                                        <td className="px-3 py-2 font-mono text-xs">
                                            {row.event_hash}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {logs.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {logs.links.map((link, i) =>
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
