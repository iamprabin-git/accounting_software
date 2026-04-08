import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Edit({ member, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, put, processing, errors } = useForm({
        reference_code: member.reference_code || '',
        name: member.name,
        email: member.email || '',
        phone: member.phone || '',
        address: member.address || '',
        notes: member.notes || '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        put(
            route('members.update', { member: member.id }),
        );
    };

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Edit member
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="members.edit"
                        routeParams={{ member: member.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Edit member" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-muted-foreground">
                        <span className="font-semibold tabular-nums text-gray-900">
                            Member #{member.member_number}
                        </span>
                        <span className="mx-2 text-gray-400">·</span>
                        Status:{' '}
                        <span className="font-medium">{member.status}</span>
                        {member.status === 'pending' && (
                            <>
                                {' '}
                                — awaiting company approval before use in finance.
                            </>
                        )}
                    </p>
                    <Card>
                        <CardHeader className="pb-4">
                            <CardTitle className="text-base">Member details</CardTitle>
                        </CardHeader>
                        <CardContent>
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
                            <InputLabel htmlFor="name" value="Full name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="reference_code"
                                value="Reference code (optional)"
                            />
                            <TextInput
                                id="reference_code"
                                className="mt-1 block w-full"
                                value={data.reference_code}
                                onChange={(e) =>
                                    setData('reference_code', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.reference_code}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                className="mt-1 block w-full"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                            />
                            <InputError message={errors.phone} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="address" value="Address" />
                            <textarea
                                id="address"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.address}
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

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={route('members.index', companyQuery)}
                            >
                                Back
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
