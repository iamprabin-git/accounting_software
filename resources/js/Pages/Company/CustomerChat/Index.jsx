import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { Head, Link } from '@inertiajs/react';

export default function Index({ threads }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Customer messages
                </h2>
            }
        >
            <Head title="Customer messages" />

            <div className="py-10">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow sm:rounded-lg">
                        <ul className="divide-y divide-gray-200">
                            {threads.length === 0 ? (
                                <li className="px-6 py-10 text-center text-sm text-gray-500">
                                    No conversations yet. When an end user sends
                                    a message from their portal, the thread
                                    appears here.
                                </li>
                            ) : (
                                threads.map((t) => (
                                    <li key={t.end_user_id}>
                                        <Link
                                            href={route(
                                                'company.customer-chat.show',
                                                t.end_user_id,
                                            )}
                                            className="block px-6 py-4 hover:bg-gray-50"
                                        >
                                            <p className="font-medium text-gray-900">
                                                {t.name}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {t.email}
                                            </p>
                                            {t.last_body && (
                                                <p className="mt-2 text-sm text-gray-600 line-clamp-2">
                                                    {t.last_body}
                                                </p>
                                            )}
                                            {t.last_at && (
                                                <p className="mt-1 text-xs text-gray-400">
                                                    {formatDisplayDateTime(
                                                        t.last_at,
                                                    )}
                                                </p>
                                            )}
                                        </Link>
                                    </li>
                                ))
                            )}
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
