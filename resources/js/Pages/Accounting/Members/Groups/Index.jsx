import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Index({
    groups,
    approvedMembers,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const { errors } = usePage().props;
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        name: '',
        code: '',
        notes: '',
        member_ids: [],
        ...query,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('member-groups.store', query));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Member groups</h2>
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
                    <InputError message={errors.status} className="mb-2" />
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm uppercase tracking-wide text-muted-foreground">
                                Create group
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="grid gap-3 md:grid-cols-2">
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Group name</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.name} className="mt-1" />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Code (optional)</label>
                                <input
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value)}
                                />
                                <InputError message={form.errors.code} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-xs font-medium text-gray-700">Members</label>
                                <select
                                    multiple
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.member_ids}
                                    onChange={(e) =>
                                        form.setData(
                                            'member_ids',
                                            Array.from(e.target.selectedOptions).map((o) => Number(o.value)),
                                        )
                                    }
                                >
                                    {approvedMembers.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            #{m.member_number ?? '-'} {m.name} {m.reference_code ? `(${m.reference_code})` : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.member_ids} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <label className="block text-xs font-medium text-gray-700">Notes</label>
                                <textarea
                                    rows={2}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    value={form.data.notes}
                                    onChange={(e) => form.setData('notes', e.target.value)}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Button type="submit" size="sm" disabled={form.processing}>
                                    Save group
                                </Button>
                            </div>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="overflow-hidden rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Members</th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {groups.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-6 text-center text-sm text-gray-500">No groups yet.</td>
                                    </tr>
                                ) : (
                                    groups.data.map((group) => (
                                        <tr key={group.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">{group.name}</td>
                                            <td className="px-4 py-3 text-sm text-gray-700">{group.code || '—'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-700">{group.members_count}</td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route('member-groups.show', { group: group.id, ...query })}
                                                    className="text-indigo-600 hover:text-indigo-800"
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
