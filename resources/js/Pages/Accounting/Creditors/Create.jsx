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
        reference: '',
        balance: '0',
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
        post(route('creditors.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        New creditor
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="creditors.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New creditor" />

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
                                htmlFor="reference"
                                value="Reference (optional)"
                            />
                            <TextInput
                                id="reference"
                                className="mt-1 block w-full"
                                value={data.reference}
                                onChange={(e) =>
                                    setData('reference', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.reference}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="balance"
                                value="Balance you owe (dollars)"
                            />
                            <TextInput
                                id="balance"
                                type="number"
                                step="0.01"
                                className="mt-1 block w-full"
                                value={data.balance}
                                onChange={(e) =>
                                    setData('balance', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.balance}
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
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>Save</PrimaryButton>
                            <Link
                                href={route('creditors.index', {
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
