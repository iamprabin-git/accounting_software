import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
export default function Create({ companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        industry: '',
        website: '',
        phone: '',
        email: '',
        address: '',
        notes: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        post(route('crm.accounts.store'));
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        New CRM account
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.accounts.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New CRM account" />
            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg dark:bg-gray-900"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
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
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800"
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
                                rows={3}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.notes}
                                className="mt-2"
                            />
                        </div>
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('crm.accounts.index', q)}
                                className="text-sm text-gray-600 underline dark:text-gray-400"
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
