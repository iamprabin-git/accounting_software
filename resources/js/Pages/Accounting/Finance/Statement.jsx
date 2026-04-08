import { Button } from '@/Components/ui/button';
import { formatStatementAtParts } from '@/utils/dateDisplay';
import { moneyFromCents } from '@/utils/money';
import { Head } from '@inertiajs/react';

function StatementDateCell({ at }) {
    const parts = formatStatementAtParts(at);
    if (!parts) {
        return '—';
    }
    return (
        <div className="leading-snug">
            <div className="text-gray-800">{parts.adLine}</div>
            {parts.bsLine ? (
                <div className="mt-0.5 text-xs text-gray-500 tabular-nums print:text-gray-600">
                    <span className="font-medium text-gray-600 print:text-gray-800">
                        BS
                    </span>{' '}
                    {parts.bsLine}
                </div>
            ) : null}
        </div>
    );
}

export default function Statement({
    categoryLabel,
    letterhead,
    position,
    movements,
}) {
    const printNow = () => window.print();

    return (
        <div className="min-h-screen bg-white text-gray-900 print:p-8">
            <Head title={`Statement — ${position.title}`} />

            <div className="mx-auto max-w-3xl p-8 print:max-w-none print:p-0">
                <div className="print:hidden">
                    <Button type="button" size="sm" onClick={printNow}>
                        Print
                    </Button>
                </div>

                <header className="mt-6 border-b border-gray-200 pb-4">
                    <h1 className="text-xl font-bold">{letterhead.name}</h1>
                    {letterhead.address && (
                        <p className="text-sm text-gray-600 whitespace-pre-line">
                            {letterhead.address}
                        </p>
                    )}
                    {letterhead.phone && (
                        <p className="text-sm text-gray-600">{letterhead.phone}</p>
                    )}
                </header>

                <section className="mt-6">
                    <h2 className="text-lg font-semibold">
                        {categoryLabel} statement
                    </h2>
                    <p className="mt-2 text-sm">
                        <span className="font-medium">Product:</span>{' '}
                        {position.title}
                    </p>
                    {position.account_number && (
                        <p className="mt-1 text-sm">
                            <span className="font-medium">Account:</span>{' '}
                            <span className="font-mono tabular-nums">
                                {position.account_number}
                            </span>
                        </p>
                    )}
                    {position.member && (
                        <p className="text-sm">
                            <span className="font-medium">Member:</span> #
                            {position.member.member_number}{' '}
                            {position.member.name}
                        </p>
                    )}
                    <p className="mt-1 text-sm">
                        <span className="font-medium">Current balance:</span>{' '}
                        <span className="tabular-nums font-semibold">
                            {moneyFromCents(position.principal_cents)}
                        </span>
                    </p>
                    <p className="mt-3 text-xs text-gray-500 print:text-gray-600">
                        Dates show{' '}
                        <span className="font-medium">AD</span> (Gregorian) and{' '}
                        <span className="font-medium">BS</span> (Bikram Sambat /
                        Nepali calendar) for each line.
                    </p>
                </section>

                <div className="mt-8 overflow-x-auto print:overflow-visible">
                <table className="w-full min-w-[520px] border-collapse text-sm">
                    <thead>
                        <tr className="border-b border-gray-300 text-left">
                            <th className="py-2 pr-2 align-bottom">
                                Date / time
                                <span className="mt-0.5 block text-[10px] font-normal text-gray-500 normal-case">
                                    AD + BS
                                </span>
                            </th>
                            <th className="py-2 pr-2 align-bottom">
                                Description
                            </th>
                            <th className="py-2 pr-2 text-right align-bottom">
                                Change
                            </th>
                            <th className="py-2 text-right align-bottom">
                                Balance
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {movements.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={4}
                                    className="py-6 text-center text-gray-500"
                                >
                                    No activity recorded.
                                </td>
                            </tr>
                        ) : (
                            movements.map((m, i) => (
                                <tr key={i} className="border-b border-gray-100">
                                    <td className="py-2 pr-2 align-top text-gray-700">
                                        <StatementDateCell at={m.at} />
                                    </td>
                                    <td className="py-2 pr-2 align-top">
                                        {m.type_label}
                                        {m.memo ? (
                                            <span className="block text-xs text-gray-500">
                                                {m.memo}
                                            </span>
                                        ) : null}
                                    </td>
                                    <td className="py-2 pr-2 text-right align-top tabular-nums">
                                        {moneyFromCents(m.amount_cents)}
                                    </td>
                                    <td className="py-2 text-right align-top font-medium tabular-nums">
                                        {moneyFromCents(m.balance_after_cents)}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    );
}
