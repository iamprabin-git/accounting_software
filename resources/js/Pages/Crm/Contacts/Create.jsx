import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Create({ accounts, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        crm_account_id: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        job_title: '',
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
        post(route('crm.contacts.store'));
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        New contact
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.contacts.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New CRM contact" />
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
                        <div>
                            <InputLabel
                                htmlFor="crm_account_id"
                                value="Organization account (optional)"
                            />
                            <select
                                id="crm_account_id"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800"
                                value={data.crm_account_id}
                                onChange={(e) =>
                                    setData('crm_account_id', e.target.value)
                                }
                            >
                                <option value="">— None —</option>
                                {accounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.crm_account_id}
                                className="mt-2"
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="first_name"
                                    value="First name"
                                />
                                <TextInput
                                    id="first_name"
                                    className="mt-1 block w-full"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.first_name}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="last_name"
                                    value="Last name"
                                />
                                <TextInput
                                    id="last_name"
                                    className="mt-1 block w-full"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData('last_name', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.last_name}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                        {[
                            ['email', 'Email', 'email'],
                            ['phone', 'Phone', 'text'],
                            ['job_title', 'Job title', 'text'],
                        ].map(([field, label, type]) => (
                            <div key={field}>
                                <InputLabel htmlFor={field} value={label} />
                                <TextInput
                                    id={field}
                                    type={type}
                                    className="mt-1 block w-full"
                                    value={data[field]}
                                    onChange={(e) =>
                                        setData(field, e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors[field]}
                                    className="mt-2"
                                />
                            </div>
                        ))}
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
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('crm.contacts.index', q)}
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
