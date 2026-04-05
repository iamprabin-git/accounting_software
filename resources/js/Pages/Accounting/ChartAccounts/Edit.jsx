import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Edit({
    account,
    companies,
    currentCompanyId,
    accountTypes,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, put, processing, errors } = useForm({
        code: account.code,
        name: account.name,
        type: account.type,
        description: account.description || '',
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
            route('chart-accounts.update', {
                account: account.id,
            }),
        );
    };

    const typeEntries = Object.entries(accountTypes || {});

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Edit account {account.code}
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="chart-accounts.edit"
                        routeParams={{ account: account.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`Edit ${account.code}`} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id || currentCompanyId}
                            />
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="code" value="Code" />
                                <TextInput
                                    id="code"
                                    className="mt-1 block w-full"
                                    value={data.code}
                                    onChange={(e) =>
                                        setData('code', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.code}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="type" value="Type" />
                                <select
                                    id="type"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.type}
                                    onChange={(e) =>
                                        setData('type', e.target.value)
                                    }
                                    required
                                >
                                    {typeEntries.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={errors.type}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Name" />
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
                                htmlFor="description"
                                value="Description"
                            />
                            <textarea
                                id="description"
                                rows={3}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Update account
                            </PrimaryButton>
                            <Link
                                href={route('chart-accounts.index', {
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                                className="inline-flex items-center text-sm text-gray-600 underline"
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
