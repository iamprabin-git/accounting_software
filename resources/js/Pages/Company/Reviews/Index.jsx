import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

const statusBadgeClass = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    approved:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
};

export default function Index({ reviews }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Review Approvals
                </h2>
            }
        >
            <Head title="Review Approvals" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p className="text-sm text-gray-600 dark:text-slate-300">
                                Approve user-submitted frontend reviews before they
                                appear on the public homepage.
                            </p>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                            <thead className="bg-gray-50 dark:bg-slate-950/40">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        User
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Review
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Rating
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                {reviews.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-10 text-center text-sm text-gray-500"
                                        >
                                            No reviews submitted yet.
                                        </td>
                                    </tr>
                                ) : (
                                    reviews.map((review) => (
                                        <tr key={review.id}>
                                            <td className="px-4 py-4 text-sm">
                                                <p className="font-medium text-gray-900 dark:text-slate-100">
                                                    {review.author_name}
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-slate-400">
                                                    {review.author_email || '—'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-gray-700 dark:text-slate-200">
                                                <p className="font-medium">
                                                    {review.title || 'User review'}
                                                </p>
                                                <p className="mt-1 line-clamp-3 text-xs text-gray-600 dark:text-slate-300">
                                                    {review.body}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4 text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {review.rating}/5
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusBadgeClass[review.status] || ''}`}
                                                >
                                                    {review.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-right text-sm">
                                                {review.status === 'pending' ? (
                                                    <div className="inline-flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            className="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                                            onClick={() =>
                                                                router.post(
                                                                    route(
                                                                        'company.reviews.approve',
                                                                        review.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Approve
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                                                            onClick={() =>
                                                                router.post(
                                                                    route(
                                                                        'company.reviews.reject',
                                                                        review.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-gray-500 dark:text-slate-400">
                                                        Reviewed
                                                    </span>
                                                )}
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
