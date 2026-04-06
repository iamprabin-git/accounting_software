import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Index({
    groups,
    eligibleMembers,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        code: '',
        name: '',
        meeting_day: '',
        notes: '',
        member_ids: [],
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('member-groups.store', query));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Member groups
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="member-groups.index"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Member groups" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-5 shadow"
                    >
                        <p className="text-sm text-gray-600">
                            Create a center/group for collection meetings. You can
                            then post one group deposit batch that updates member
                            savings ledgers with audit-trail proof.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Code (optional)"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                            />
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Group name"
                                required
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                            />
                            <input
                                className="rounded-md border-gray-300 text-sm"
                                placeholder="Meeting day (e.g. Monday)"
                                value={form.data.meeting_day}
                                onChange={(e) =>
                                    form.setData('meeting_day', e.target.value)
                                }
                            />
                        </div>
                        <textarea
                            className="w-full rounded-md border-gray-300 text-sm"
                            rows={2}
                            placeholder="Notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                        <div>
                            <p className="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Add approved members (optional)
                            </p>
                            <div className="max-h-40 overflow-y-auto rounded border p-2">
                                <div className="grid gap-1 sm:grid-cols-2">
                                    {eligibleMembers.map((m) => {
                                        const checked = form.data.member_ids.includes(m.id);
                                        return (
                                            <label
                                                key={m.id}
                                                className="flex items-center gap-2 text-sm text-gray-700"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        const next = checked
                                                            ? form.data.member_ids.filter((id) => id !== m.id)
                                                            : [...form.data.member_ids, m.id];
                                                        form.setData('member_ids', next);
                                                    }}
                                                />
                                                <span>{m.label}</span>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Create group
                        </button>
                    </form>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left">Group</th>
                                    <th className="px-3 py-2 text-left">Meeting</th>
                                    <th className="px-3 py-2 text-right">Members</th>
                                    <th className="px-3 py-2 text-right">Batches</th>
                                    <th className="px-3 py-2 text-right">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {groups.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-6 text-center text-gray-500"
                                        >
                                            No groups yet.
                                        </td>
                                    </tr>
                                ) : (
                                    groups.data.map((g) => (
                                        <tr key={g.id}>
                                            <td className="px-3 py-2">
                                                <div className="font-medium text-gray-900">
                                                    {g.name}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {g.code || '—'} · {g.status}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">{g.meeting_day || '—'}</td>
                                            <td className="px-3 py-2 text-right">
                                                {g.active_members_count}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                {g.deposit_batches_count}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <Link
                                                    href={route('member-groups.show', {
                                                        group: g.id,
                                                        ...query,
                                                    })}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Open
                                                </Link>
                                            </td>
                                        </tr>
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

