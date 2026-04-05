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
        product_code: '',
        name: '',
        default_annual_interest_rate_percent: '0',
        notes: '',
        is_active: true,
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', String(currentCompanyId));
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.loan-products.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-900">
                        New loan product
                    </h2>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="finance.loan-products.create"
                            routeParams={{}}
                            query={{}}
                        />
                    )}
                </div>
            }
        >
            <Head title="New loan product" />

            <div className="py-8">
                <div className="mx-auto max-w-xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-5 bg-white p-6 shadow sm:rounded-lg"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={
                                    data.company_id || String(currentCompanyId)
                                }
                            />
                        )}
                        <div>
                            <InputLabel
                                htmlFor="product_code"
                                value="Product code"
                            />
                            <TextInput
                                id="product_code"
                                className="mt-1 block w-full font-mono"
                                value={data.product_code}
                                onChange={(e) =>
                                    setData('product_code', e.target.value)
                                }
                                placeholder="e.g. 11"
                                required
                            />
                            <InputError
                                message={errors.product_code}
                                className="mt-2"
                            />
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
                                htmlFor="default_annual_interest_rate_percent"
                                value="Default annual interest %"
                            />
                            <TextInput
                                id="default_annual_interest_rate_percent"
                                type="number"
                                step="0.0001"
                                min="0"
                                max="100"
                                className="mt-1 block w-full"
                                value={
                                    data.default_annual_interest_rate_percent
                                }
                                onChange={(e) =>
                                    setData(
                                        'default_annual_interest_rate_percent',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={
                                    errors.default_annual_interest_rate_percent
                                }
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
                        <div className="flex items-center gap-2">
                            <input
                                id="is_active"
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData('is_active', e.target.checked)
                                }
                                className="rounded border-gray-300"
                            />
                            <label
                                htmlFor="is_active"
                                className="text-sm text-gray-700"
                            >
                                Active (available for new applications)
                            </label>
                        </div>
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('finance.loan-products.index', {
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
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
