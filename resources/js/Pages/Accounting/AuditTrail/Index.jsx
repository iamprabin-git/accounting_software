import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Index({
    logs,
    filters,
    action_options = [],
    summary = {},
    companies,
    currentCompanyId,
    integrity,
    last_verification,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const presetStorageKey = useMemo(
        () => `audit-filter-presets:${currentCompanyId || 'default'}`,
        [currentCompanyId],
    );
    const [presetName, setPresetName] = useState('');
    const [presetRefresh, setPresetRefresh] = useState(0);

    const query = {
        ...(filters?.action ? { action: filters.action } : {}),
        ...(filters?.action_like ? { action_like: filters.action_like } : {}),
        ...(filters?.from_date ? { from_date: filters.from_date } : {}),
        ...(filters?.to_date ? { to_date: filters.to_date } : {}),
        ...(filters?.journal_entry_id
            ? { journal_entry_id: filters.journal_entry_id }
            : {}),
        ...(filters?.actor_name ? { actor_name: filters.actor_name } : {}),
        ...(filters?.hash ? { hash: filters.hash } : {}),
    };

    const withCompany = (params = {}) =>
        isAdmin && currentCompanyId
            ? { ...params, company_id: currentCompanyId }
            : params;

    const filterPayload = {
        action: filters?.action ?? '',
        action_like: filters?.action_like ?? '',
        journal_entry_id: filters?.journal_entry_id ?? '',
        from_date: filters?.from_date ?? '',
        to_date: filters?.to_date ?? '',
        actor_name: filters?.actor_name ?? '',
        hash: filters?.hash ?? '',
    };

    const savedPresets = useMemo(() => {
        try {
            const raw = window.localStorage.getItem(presetStorageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }, [presetStorageKey, presetRefresh]);

    const savePreset = () => {
        const name = presetName.trim();
        if (!name) return;
        try {
            const next = [
                {
                    name,
                    filters: filterPayload,
                },
                ...savedPresets.filter((p) => p?.name !== name),
            ].slice(0, 12);
            window.localStorage.setItem(presetStorageKey, JSON.stringify(next));
            setPresetName('');
            setPresetRefresh((n) => n + 1);
        } catch {
            // no-op if storage is unavailable
        }
    };

    const applyPreset = (preset) => {
        const f = preset?.filters ?? {};
        router.get(
            route('audit-trail.index', withCompany({})),
            Object.fromEntries(
                Object.entries({
                    action: f.action || '',
                    action_like: f.action_like || '',
                    journal_entry_id: f.journal_entry_id || '',
                    from_date: f.from_date || '',
                    to_date: f.to_date || '',
                    actor_name: f.actor_name || '',
                    hash: f.hash || '',
                }).filter(([, v]) => v !== ''),
            ),
        );
    };

    const builtInPresets = [
        {
            name: 'Integrity incidents',
            filters: { action_like: 'audit.integrity_' },
        },
        {
            name: 'Approval actions',
            filters: { action_like: 'approve' },
        },
        {
            name: 'Journal-specific events',
            filters: { action_like: 'journal.' },
        },
    ];

    const deletePreset = (name) => {
        try {
            const next = savedPresets.filter((p) => p?.name !== name);
            window.localStorage.setItem(presetStorageKey, JSON.stringify(next));
            setPresetRefresh((n) => n + 1);
        } catch {
            // no-op
        }
    };

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
                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="cbs-surface p-4">
                            <p className="text-xs text-gray-500">Events today</p>
                            <p className="text-2xl font-semibold">
                                {summary.today_events ?? 0}
                            </p>
                        </div>
                        <div className="cbs-surface p-4">
                            <p className="text-xs text-gray-500">
                                Integrity failures
                            </p>
                            <p className="text-2xl font-semibold">
                                {summary.failed_integrity_events ?? 0}
                            </p>
                        </div>
                        <div className="cbs-surface p-4">
                            <p className="text-xs text-gray-500">
                                Unique actors (7d)
                            </p>
                            <p className="text-2xl font-semibold">
                                {summary.unique_actors_7d ?? 0}
                            </p>
                        </div>
                    </div>

                    <form
                        className="grid gap-3 rounded bg-white p-4 shadow sm:grid-cols-8"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const fd = new FormData(e.currentTarget);
                            const next = {
                                action: (fd.get('action') || '').toString(),
                                action_like: (
                                    fd.get('action_like') || ''
                                ).toString(),
                                from_date: (fd.get('from_date') || '').toString(),
                                to_date: (fd.get('to_date') || '').toString(),
                                journal_entry_id: (
                                    fd.get('journal_entry_id') || ''
                                ).toString(),
                                actor_name: (
                                    fd.get('actor_name') || ''
                                ).toString(),
                                hash: (fd.get('hash') || '').toString(),
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
                        <select
                            name="action"
                            defaultValue={filters?.action ?? ''}
                            className="rounded-md border-gray-300 text-sm"
                        >
                            <option value="">All actions</option>
                            {action_options.map((a) => (
                                <option key={a} value={a}>
                                    {a}
                                </option>
                            ))}
                        </select>
                        <input
                            name="action_like"
                            defaultValue={filters?.action_like ?? ''}
                            placeholder="Action contains…"
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
                        <input
                            name="actor_name"
                            defaultValue={filters?.actor_name ?? ''}
                            placeholder="Actor name"
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <input
                            name="hash"
                            defaultValue={filters?.hash ?? ''}
                            placeholder="Hash contains…"
                            className="rounded-md border-gray-300 text-sm"
                        />
                        <button className="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">
                            Apply filters
                        </button>
                    </form>
                    <div className="rounded bg-white p-4 shadow">
                        <p className="text-sm font-semibold text-gray-900">
                            Save this filter preset
                        </p>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <input
                                value={presetName}
                                onChange={(e) => setPresetName(e.target.value)}
                                placeholder="Preset name (e.g. Integrity incidents)"
                                className="rounded-md border-gray-300 text-sm"
                            />
                            <button
                                type="button"
                                onClick={savePreset}
                                className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            >
                                Save preset
                            </button>
                        </div>
                        <p className="mt-3 text-xs font-semibold text-gray-700">
                            Built-in presets
                        </p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {builtInPresets.map((p) => (
                                <button
                                    key={p.name}
                                    type="button"
                                    className="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                    onClick={() => applyPreset(p)}
                                >
                                    {p.name}
                                </button>
                            ))}
                        </div>
                        <p className="mt-3 text-xs font-semibold text-gray-700">
                            Your saved presets
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {savedPresets.length === 0 ? (
                                <p className="text-xs text-gray-500">
                                    No saved presets yet.
                                </p>
                            ) : (
                                savedPresets.map((p) => (
                                    <div
                                        key={p.name}
                                        className="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-2 py-1"
                                    >
                                        <button
                                            type="button"
                                            className="text-xs font-medium text-indigo-700 hover:text-indigo-900"
                                            onClick={() => applyPreset(p)}
                                        >
                                            {p.name}
                                        </button>
                                        <button
                                            type="button"
                                            className="text-xs text-gray-500 hover:text-red-700"
                                            onClick={() =>
                                                deletePreset(p.name)
                                            }
                                            aria-label={`Delete preset ${p.name}`}
                                        >
                                            ✕
                                        </button>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    <div
                        className={`cbs-surface border p-4 ${
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
                                    <th className="px-3 py-2 text-left">
                                        Previous
                                    </th>
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
                                        <td className="px-3 py-2 font-mono text-xs">
                                            {row.previous_event_hash || '—'}
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
