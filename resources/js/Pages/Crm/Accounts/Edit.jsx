import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Edit({ account, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, put, processing, errors } = useForm({
        name: account.name,
        industry: account.industry ?? '',
        website: account.website ?? '',
        phone: account.phone ?? '',
        email: account.email ?? '',
        address: account.address ?? '',
        notes: account.notes ?? '',
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
            route('crm.accounts.update', {
                account: account.id,
            }),
        );
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Edit account
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.accounts.edit"
                        routeParams={{ account: account.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Edit CRM account" />
            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg dark:bg-gray-900"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                value={data.company_id || currentCompanyId}
                            />
                        )}
                        {[
                            ['name', 'Name', true],
                            ['industry', 'Industry', false],
                            ['website', 'Website', false],
                            ['phone', 'Phone', false],
                            ['email', 'Email', false],
                        ].map(([field, label, req]) => (
                            <div key={field}>
                                <InputLabel htmlFor={field} value={label} />
                                <TextInput
                                    id={field}
                                    type={
                                        field === 'email' ? 'email' : 'text'
                                    }
                                    className="mt-1 block w-full"
                                    value={data[field]}
                                    onChange={(e) =>
                                        setData(field, e.target.value)
                                    }
                                    required={req}
                                />
                                <InputError
                                    message={errors[field]}
                                    className="mt-2"
                                />
                            </div>
                        ))}
                        <div>
                            <InputLabel htmlFor="address" value="Address" />
                            <textarea
                                id="address"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="notes" value="Notes" />
                            <textarea
                                id="notes"
                                rows={3}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                        </div>
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Update
                            </PrimaryButton>
                            <Link
                                href={route('crm.accounts.show', {
                                    account: account.id,
                                    ...q,
                                })}
                                className="text-sm text-gray-600 underline"
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
