import CompanyPicker from '@/Components/CompanyPicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ items, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const destroy = (id) => {
        if (!confirm('Delete this inventory item?')) return;
        const opts =
            isAdmin && currentCompanyId
                ? { data: { company_id: currentCompanyId } }
                : {};
        router.delete(route('inventory.destroy', { item: id }), opts);
    };

    const methodLabel = (m) =>
        m === 'lifo' ? 'LIFO' : 'FIFO';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Inventory
                    </h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="inventory.index"
                            routeParams={{}}
                            query={{}}
                        />
                        <Link
                            href={route('inventory.create', companyQuery)}
                            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                        >
                            New item
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Inventory" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600">
                        Use <strong>Stock</strong> to record purchases (add
                        layers at cost) and sales (reduce using FIFO or LIFO).
                        Closing quantity and value follow the open cost layers.
                        Totals still roll to trial balance when an inventory
                        account is linked under company profile.
                    </p>

                    <div className="overflow-x-auto bg-white shadow sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        SKU
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Method
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Qty
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Avg unit
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Value
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No inventory items yet.
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((row) => (
                                        <tr key={row.id}>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {row.sku || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {row.name}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                                {methodLabel(
                                                    row.valuation_method,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-700">
                                                {row.quantity}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-700">
                                                {moneyFromCents(
                                                    row.unit_cost_cents,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums font-medium text-gray-900">
                                                {moneyFromCents(
                                                    row.value_at_cost_cents,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={route(
                                                        'inventory.show',
                                                        {
                                                            item: row.id,
                                                            ...companyQuery,
                                                        },
                                                    )}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Stock
                                                </Link>
                                                <Link
                                                    href={route(
                                                        'inventory.edit',
                                                        {
                                                            item: row.id,
                                                            ...companyQuery,
                                                        },
                                                    )}
                                                    className="ms-3 text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(row.id)
                                                    }
                                                    className="ms-3 text-red-600 hover:text-red-800"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {items.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-1">
                            {items.links.map((link, i) =>
                                link.url ? (
                                    <button
                                        key={i}
                                        type="button"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-gray-800 text-white'
                                                : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'
                                        }`}
                                        onClick={() =>
                                            router.get(link.url, {}, {
                                                preserveState: true,
                                            })
                                        }
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className="px-3 py-1 text-sm text-gray-400"
                                    />
                                ),
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
