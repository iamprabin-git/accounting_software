import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Show({
    item,
    lots,
    movements,
    companies,
    currentCompanyId,
}) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const flash = page.props.flash ?? {};
    const errors = page.props.errors ?? {};

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const today = new Date().toISOString().slice(0, 10);

    const purchaseForm = useForm({
        quantity: '',
        unit_cost: '',
        transaction_date: today,
        reference: '',
        notes: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    const saleForm = useForm({
        quantity: '',
        transaction_date: today,
        reference: '',
        notes: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            purchaseForm.setData('company_id', String(currentCompanyId));
            saleForm.setData('company_id', String(currentCompanyId));
        }
    }, [currentCompanyId, isAdmin]);

    const submitPurchase = (e) => {
        e.preventDefault();
        purchaseForm.post(
            route('inventory.purchase', { item: item.id }),
        );
    };

    const submitSale = (e) => {
        e.preventDefault();
        saleForm.post(route('inventory.sale', { item: item.id }));
    };

    const methodLabel =
        item.valuation_method === 'lifo' ? 'LIFO' : 'FIFO';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Stock — {item.name}
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="inventory.show"
                            routeParams={{ item: item.id }}
                            query={{}}
                        />
                        <Link
                            href={route('inventory.edit', {
                                item: item.id,
                                ...companyQuery,
                            })}
                            className="text-sm text-indigo-600 underline"
                        >
                            Edit item
                        </Link>
                        <Link
                            href={route('inventory.index', companyQuery)}
                            className="text-sm text-gray-600 underline"
                        >
                            All items
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Stock — ${item.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                    {flash.status && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.status}
                        </div>
                    )}

                    <Card>
                        <CardContent className="p-4 sm:p-6">
                        <div className="flex flex-wrap gap-6 text-sm">
                            <div>
                                <span className="text-gray-500">Closing qty</span>
                                <p className="text-lg font-semibold tabular-nums text-gray-900">
                                    {item.quantity}
                                </p>
                            </div>
                            <div>
                                <span className="text-gray-500">
                                    Avg unit cost (rolled up)
                                </span>
                                <p className="text-lg font-semibold tabular-nums text-gray-900">
                                    {moneyFromCents(item.unit_cost_cents)}
                                </p>
                            </div>
                            <div>
                                <span className="text-gray-500">
                                    Value at cost (closing)
                                </span>
                                <p className="text-lg font-semibold tabular-nums text-gray-900">
                                    {moneyFromCents(item.value_at_cost_cents)}
                                </p>
                            </div>
                            <div>
                                <span className="text-gray-500">Valuation</span>
                                <p className="font-semibold text-gray-900">
                                    {methodLabel}
                                </p>
                            </div>
                        </div>
                        <p className="mt-3 text-sm text-gray-600">
                            <strong>Purchases</strong> add layers at the cost you
                            enter. <strong>Sales</strong> reduce stock using{' '}
                            <strong>{methodLabel}</strong> (first-in-first-out or
                            last-in-first-out) to value cost of goods sold for
                            that line.
                        </p>
                        </CardContent>
                    </Card>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardContent className="p-4 sm:p-6">
                        <form
                            onSubmit={submitPurchase}
                            className="space-y-4"
                        >
                            <h3 className="text-sm font-semibold text-gray-900">
                                Record purchase
                            </h3>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        purchaseForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel value="Quantity" />
                                    <TextInput
                                        type="number"
                                        step="any"
                                        min="0.0001"
                                        className="mt-1 block w-full"
                                        value={purchaseForm.data.quantity}
                                        onChange={(e) =>
                                            purchaseForm.setData(
                                                'quantity',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Unit cost (NPR)" />
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        className="mt-1 block w-full"
                                        value={purchaseForm.data.unit_cost}
                                        onChange={(e) =>
                                            purchaseForm.setData(
                                                'unit_cost',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={purchaseForm.data.transaction_date}
                                    onChange={(e) =>
                                        purchaseForm.setData(
                                            'transaction_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel value="Reference (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={purchaseForm.data.reference}
                                    onChange={(e) =>
                                        purchaseForm.setData(
                                            'reference',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <InputLabel value="Notes" />
                                <textarea
                                    rows={2}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={purchaseForm.data.notes}
                                    onChange={(e) =>
                                        purchaseForm.setData(
                                            'notes',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={purchaseForm.processing}
                            >
                                Add stock
                            </Button>
                            <InputError message={errors.quantity} />
                        </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-4 sm:p-6">
                        <form
                            onSubmit={submitSale}
                            className="space-y-4"
                        >
                            <h3 className="text-sm font-semibold text-gray-900">
                                Record sale
                            </h3>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        saleForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <div>
                                <InputLabel value="Quantity" />
                                <TextInput
                                    type="number"
                                    step="any"
                                    min="0.0001"
                                    className="mt-1 block w-full"
                                    value={saleForm.data.quantity}
                                    onChange={(e) =>
                                        saleForm.setData(
                                            'quantity',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel value="Date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={saleForm.data.transaction_date}
                                    onChange={(e) =>
                                        saleForm.setData(
                                            'transaction_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel value="Reference (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={saleForm.data.reference}
                                    onChange={(e) =>
                                        saleForm.setData(
                                            'reference',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <InputLabel value="Notes" />
                                <textarea
                                    rows={2}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={saleForm.data.notes}
                                    onChange={(e) =>
                                        saleForm.setData(
                                            'notes',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={saleForm.processing}
                            >
                                Reduce stock ({methodLabel})
                            </Button>
                            <InputError message={errors.quantity} />
                        </form>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow">
                        <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Open cost layers (lots)
                            </h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        Received
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        Remaining
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        Unit cost
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        Value
                                    </th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        Ref.
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {lots.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-6 text-center text-sm text-gray-500"
                                        >
                                            No open lots — record a purchase or
                                            opening balance.
                                        </td>
                                    </tr>
                                ) : (
                                    lots.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-2 text-sm text-gray-800">
                                                {row.received_at}
                                            </td>
                                            <td className="px-4 py-2 text-right text-sm tabular-nums text-gray-800">
                                                {row.quantity_remaining}
                                            </td>
                                            <td className="px-4 py-2 text-right text-sm tabular-nums text-gray-700">
                                                {moneyFromCents(
                                                    row.unit_cost_cents,
                                                )}
                                            </td>
                                            <td className="px-4 py-2 text-right text-sm tabular-nums font-medium text-gray-900">
                                                {moneyFromCents(
                                                    row.value_remaining_cents,
                                                )}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-600">
                                                {row.reference || '—'}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow">
                        <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <h3 className="text-sm font-semibold text-gray-900">
                                Recent movements
                            </h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        Date
                                    </th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        Type
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        Qty
                                    </th>
                                    <th className="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        Cost / COGS
                                    </th>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        By
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {movements.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-6 text-center text-sm text-gray-500"
                                        >
                                            No movements yet.
                                        </td>
                                    </tr>
                                ) : (
                                    movements.map((m) => (
                                        <tr key={m.id}>
                                            <td className="px-4 py-2 text-sm text-gray-800">
                                                {m.transaction_date}
                                            </td>
                                            <td className="px-4 py-2 text-sm capitalize text-gray-800">
                                                {m.type}
                                            </td>
                                            <td className="px-4 py-2 text-right text-sm tabular-nums text-gray-800">
                                                {m.quantity}
                                            </td>
                                            <td className="px-4 py-2 text-right text-sm tabular-nums text-gray-800">
                                                {moneyFromCents(
                                                    m.total_cost_cents,
                                                )}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-600">
                                                {m.user_name || '—'}
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
