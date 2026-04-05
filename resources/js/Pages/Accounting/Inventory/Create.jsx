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
        sku: '',
        name: '',
        quantity: '0',
        unit_cost: '0',
        valuation_method: 'fifo',
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
        post(route('inventory.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        New inventory item
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="inventory.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New inventory item" />

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
                                <option value="fifo">
                                    FIFO — sell oldest layers first
                                </option>
                                <option value="lifo">
                                    LIFO — sell newest layers first
                                </option>
                            </select>
                            <InputError
                                message={errors.valuation_method}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="quantity"
                                    value="Opening quantity"
                                />
                                <TextInput
                                    id="quantity"
                                    type="number"
                                    step="any"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.quantity}
                                    onChange={(e) =>
                                        setData('quantity', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.quantity}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="unit_cost"
                                    value="Opening unit cost (NPR)"
                                />
                                <TextInput
                                    id="unit_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.unit_cost}
                                    onChange={(e) =>
                                        setData('unit_cost', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.unit_cost}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <p className="text-sm text-gray-600">
                            If opening quantity is zero, you can add stock later
                            on the Stock page (purchases).
                        </p>

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
                                href={route('inventory.index', {
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
