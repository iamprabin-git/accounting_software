import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ customer, messages }) {
    const form = useForm({ body: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(
            route('company.customer-chat.store', customer.id),
            {
                preserveScroll: true,
                onSuccess: () => form.reset('body'),
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {customer.name}
                        </h2>
                        <p className="text-sm text-gray-500">{customer.email}</p>
                    </div>
                    <Link
                        href={route('company.customer-chat.index')}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        ← All threads
                    </Link>
                </div>
            }
        >
            <Head title={`Chat — ${customer.name}`} />

            <div className="py-10">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">
                    <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 max-h-[28rem] overflow-y-auto space-y-3">
                        {messages.length === 0 ? (
                            <p className="text-sm text-gray-500 text-center py-8">
                                No messages yet.
                            </p>
                        ) : (
                            messages.map((m) => (
                                <div
                                    key={m.id}
                                    className={`rounded-lg px-3 py-2 text-sm max-w-[90%] ${
                                        m.from_customer
                                            ? 'mr-auto bg-white border border-gray-200 shadow-sm'
                                            : 'ml-auto bg-indigo-600 text-white'
                                    }`}
                                >
                                    <p className="whitespace-pre-wrap">
                                        {m.body}
                                    </p>
                                    <p
                                        className={`text-[10px] mt-1 ${
                                            m.from_customer
                                                ? 'text-gray-500'
                                                : 'text-indigo-100'
                                        }`}
                                    >
                                        {m.from_customer
                                            ? 'Customer'
                                            : m.author_name || 'Staff'}
                                        {' · '}
                                        {m.created_at
                                            ? formatDisplayDateTime(
                                                  m.created_at,
                                              )
                                            : ''}
                                    </p>
                                </div>
                            ))
                        )}
                    </div>

                    <form
                        onSubmit={submit}
                        className="rounded-lg border border-gray-200 bg-white p-4 space-y-3"
                    >
                        <textarea
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                            rows={4}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Reply to customer…"
                        />
                        {form.errors.body && (
                            <p className="text-sm text-red-600">
                                {form.errors.body}
                            </p>
                        )}
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Send reply
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
