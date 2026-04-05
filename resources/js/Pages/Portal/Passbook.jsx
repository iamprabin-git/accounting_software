import PortalCompanyPaymentCard from '@/Components/Portal/PortalCompanyPaymentCard';
import PortalHeaderBlock from '@/Components/Portal/PortalHeaderBlock';
import PortalPageContainer from '@/Components/Portal/PortalPageContainer';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { cn } from '@/lib/utils';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { moneyFromCents } from '@/utils/money';
import { Head } from '@inertiajs/react';

function normalizeAt(at) {
    if (!at) {
        return '';
    }
    return at.includes('T') ? at : at.replace(' ', 'T');
}

function PassbookRowDisplay({ e }) {
    const at = e.at ? formatDisplayDateTime(normalizeAt(e.at)) : '';

    return (
        <>
            <td className="hidden py-3 pr-3 align-top text-sm whitespace-nowrap text-slate-600 md:table-cell dark:text-slate-400">
                {at}
            </td>
            <td className="py-3 pr-3 align-top">
                <p className="md:hidden text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {at}
                </p>
                <span className="text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-400">
                    {e.category}
                </span>
                <p className="font-medium text-slate-900 dark:text-white">
                    {e.product_title}
                </p>
            </td>
            <td className="hidden py-3 pr-3 align-top font-mono text-xs text-slate-600 lg:table-cell dark:text-slate-400">
                {e.account_number ?? '—'}
            </td>
            <td className="py-3 pr-3 align-top text-sm">
                <p className="lg:hidden font-mono text-xs text-slate-500 dark:text-slate-400">
                    {e.account_number ?? '—'}
                </p>
                <span className="text-slate-800 dark:text-slate-200">
                    {e.type_label}
                </span>
                {e.memo ? (
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {e.memo}
                    </p>
                ) : null}
            </td>
            <td className="py-3 pr-3 text-right align-top text-sm tabular-nums text-slate-800 dark:text-slate-200">
                {moneyFromCents(e.amount_cents)}
            </td>
            <td className="py-3 text-right align-top text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                {moneyFromCents(e.balance_after_cents)}
            </td>
        </>
    );
}

export default function Passbook({ entries, letterhead, payment_info }) {
    const printNow = () => window.print();

    return (
        <AuthenticatedLayout
            header={
                <PortalHeaderBlock
                    title="Digital passbook"
                    description="Chronological ledger of deposits, withdrawals, loan movements, and repayments (latest 500 lines)."
                    actions={
                        <Button
                            type="button"
                            size="sm"
                            className="print:hidden"
                            onClick={printNow}
                        >
                            Print
                        </Button>
                    }
                />
            }
        >
            <Head title="Passbook" />

            <PortalPageContainer className="print:pb-4 print:pt-4">
                <div className="print:hidden space-y-6">
                    <PortalCompanyPaymentCard payment_info={payment_info} />
                </div>

                <div className="mb-6 hidden print:block border-b border-slate-300 pb-4">
                    <h1 className="text-lg font-bold text-slate-900">
                        {letterhead.name}
                    </h1>
                    {letterhead.address ? (
                        <p className="whitespace-pre-line text-sm text-slate-700">
                            {letterhead.address}
                        </p>
                    ) : null}
                    {letterhead.phone ? (
                        <p className="text-sm text-slate-700">{letterhead.phone}</p>
                    ) : null}
                </div>

                {/* Mobile: stacked cards */}
                <div className="space-y-3 md:hidden print:hidden">
                    {entries.length === 0 ? (
                        <p className="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                            No movements recorded yet.
                        </p>
                    ) : (
                        entries.map((e) => (
                            <div
                                key={e.id}
                                className="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80"
                            >
                                <div className="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-800">
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {e.at
                                                ? formatDisplayDateTime(
                                                      normalizeAt(e.at),
                                                  )
                                                : ''}
                                        </p>
                                        <p className="mt-1 text-xs font-bold uppercase text-blue-700 dark:text-blue-400">
                                            {e.category}
                                        </p>
                                        <p className="font-semibold text-slate-900 dark:text-white">
                                            {e.product_title}
                                        </p>
                                    </div>
                                    <p className="shrink-0 text-right text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                                        {moneyFromCents(e.balance_after_cents)}
                                        <span className="mt-0.5 block text-[10px] font-normal text-slate-500">
                                            balance after
                                        </span>
                                    </p>
                                </div>
                                <dl className="mt-3 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Account
                                        </dt>
                                        <dd className="font-mono text-xs text-slate-800 dark:text-slate-200">
                                            {e.account_number ?? '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Amount
                                        </dt>
                                        <dd className="tabular-nums text-slate-800 dark:text-slate-200">
                                            {moneyFromCents(e.amount_cents)}
                                        </dd>
                                    </div>
                                    <div className="col-span-2">
                                        <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Type
                                        </dt>
                                        <dd className="text-slate-800 dark:text-slate-200">
                                            {e.type_label}
                                            {e.memo ? (
                                                <span className="mt-1 block text-xs text-slate-500">
                                                    {e.memo}
                                                </span>
                                            ) : null}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        ))
                    )}
                </div>

                {/* Tablet / desktop: table */}
                <Card className="hidden border-slate-200/90 shadow-sm md:block print:block print:border-0 print:shadow-none dark:border-slate-800">
                    <CardHeader className="print:hidden">
                        <CardTitle className="text-base">Activity register</CardTitle>
                        <CardDescription>
                            All products, one running balance column.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0 sm:p-0">
                        <div className="overflow-x-auto print:overflow-visible">
                            <table className="w-full min-w-[640px] border-collapse text-sm print:min-w-0">
                                <thead>
                                    <tr className="border-b border-slate-200 bg-slate-50/80 text-left dark:border-slate-800 dark:bg-slate-900/50">
                                        <th className="hidden py-3 pr-3 font-bold uppercase tracking-wider text-slate-600 md:table-cell dark:text-slate-400">
                                            Date / time
                                        </th>
                                        <th className="py-3 pr-3 font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                            Product
                                        </th>
                                        <th className="hidden py-3 pr-3 font-bold uppercase tracking-wider text-slate-600 lg:table-cell dark:text-slate-400">
                                            Account
                                        </th>
                                        <th className="py-3 pr-3 font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                            Type
                                        </th>
                                        <th className="py-3 pr-3 text-right font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                            Amount
                                        </th>
                                        <th className="py-3 text-right font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                            Balance after
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="py-12 text-center text-slate-500 dark:text-slate-400"
                                            >
                                                No movements recorded yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        entries.map((e, i) => (
                                            <tr
                                                key={e.id}
                                                className={cn(
                                                    'border-b border-slate-100 dark:border-slate-800/80',
                                                    i % 2 === 1 &&
                                                        'bg-slate-50/50 dark:bg-slate-900/30',
                                                )}
                                            >
                                                <PassbookRowDisplay e={e} />
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </PortalPageContainer>
        </AuthenticatedLayout>
    );
}
