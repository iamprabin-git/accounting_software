import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Edit({
    item,
    valuationOptions,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const { data, setData, put, processing, errors } = useForm({
        sku: item.sku || '',
        name: item.name,
        valuation_method: item.valuation_method || 'fifo',
        notes: item.notes || '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        put(route('inventory.update', { item: item.id }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Edit inventory
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link
                            href={route('inventory.show', {
                                item: item.id,
                                ...companyQuery,
                            })}
                            className="text-sm text-indigo-600 underline"
                        >
                            Stock &amp; movements
                        </Link>
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="inventory.edit"
                            routeParams={{ item: item.id }}
                            query={{}}
                        />
                    </div>
                </div>
            }
        >
            <Head title="Edit inventory" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600">
                        Quantity and cost come from purchases and sales on the{' '}
                        <Link
                            href={route('inventory.show', {
                                item: item.id,
                                ...companyQuery,
                            })}
                            className="text-indigo-600 underline"
                        >
                            Stock
                        </Link>{' '}
                        page.
                    </p>
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
                            <InputLabel htmlFor="sku" value="SKU (optional)" />
                            <TextInput
                                id="sku"
                                className="mt-1 block w-full"
                                value={data.sku}
                                onChange={(e) =>
                                    setData('sku', e.target.value)
                                }
                            />
                            <InputError message={errors.sku} className="mt-2" />
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
                                htmlFor="valuation_method"
                                value="Costing method (for sales)"
                            />
                            <select
                                id="valuation_method"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.valuation_method}
                                onChange={(e) =>
                                    setData(
                                        'valuation_method',
                                        e.target.value,
                                    )
                                }
                            >
                                {(valuationOptions || []).map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.valuation_method}
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
                            <PrimaryButton disabled={processing}>
                                Update
                            </PrimaryButton>
                            <Link
                                href={route('inventory.show', {
                                    item: item.id,
                                    ...companyQuery,
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
