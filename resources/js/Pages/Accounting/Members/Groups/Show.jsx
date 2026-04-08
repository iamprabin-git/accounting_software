import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Show({
    group,
    approvedMembers,
    companies,
    currentCompanyId,
    canManage,
}) {
    const user = usePage().props.auth.user ?? {};
    const { errors } = usePage().props;
    const isAdmin = user.role === 'admin';
    const query = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const form = useForm({
        member_ids: group.member_ids ?? [],
        ...query,
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('member-groups.members.sync', { group: group.id, ...query }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">{group.name}</h2>
                        <p className="text-sm text-gray-500">Member group details and assignments</p>
                    </div>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="member-groups.show"
                        routeParams={{ group: group.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`Member group - ${group.name}`} />
            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div>
                        <Link href={route('member-groups.index', query)} className="text-sm text-indigo-600 hover:text-indigo-800">
                            Back to groups
                        </Link>
                    </div>
                    <InputError message={errors.status} className="mb-2" />
                    {canManage ? (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm uppercase tracking-wide text-muted-foreground">
                                    Update members
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submit}>
                                <select
                                    multiple
                                    className="w-full rounded-md border-gray-300 text-sm"
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
                                <Button type="submit" size="sm" disabled={form.processing} className="mt-3">
                                    Save members
                                </Button>
                                </form>
                            </CardContent>
                        </Card>
                    ) : null}

                    <div className="overflow-hidden rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">No.</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Reference</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Contact</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {group.members.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-6 text-center text-sm text-gray-500">
                                            No members assigned yet.
                                        </td>
                                    </tr>
                                ) : (
                                    group.members.map((m) => (
                                        <tr key={m.id}>
                                            <td className="px-4 py-3 text-sm font-semibold text-gray-900">#{m.member_number ?? '—'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-900">{m.name}</td>
                                            <td className="px-4 py-3 text-sm text-gray-700">{m.reference_code || '—'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {[m.email, m.phone].filter(Boolean).join(' · ') || '—'}
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
