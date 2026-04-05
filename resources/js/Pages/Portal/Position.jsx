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
import { moneyFromCents } from '@/utils/money';
import { Head, Link } from '@inertiajs/react';
import QRCode from 'qrcode';
import { useEffect, useState } from 'react';

export default function Position({
    payment_info,
    category,
    category_label,
    position,
    qr_payload,
    letterhead,
}) {
    const [qrDataUrl, setQrDataUrl] = useState('');

    useEffect(() => {
        let cancelled = false;
        if (!qr_payload) {
            setQrDataUrl('');
            return undefined;
        }
        QRCode.toDataURL(qr_payload, { width: 220, margin: 2 })
            .then((url) => {
                if (!cancelled) setQrDataUrl(url);
            })
            .catch(() => {
                if (!cancelled) setQrDataUrl('');
            });
        return () => {
            cancelled = true;
        };
    }, [qr_payload]);

    const statementRoute =
        category === 'loan'
            ? route('portal.positions.statement', {
                  category: 'loan',
                  position: position.id,
              })
            : route('portal.positions.statement', {
                  category: 'savings',
                  position: position.id,
              });

    const productBadge =
        category === 'loan'
            ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200'
            : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200';

    return (
        <AuthenticatedLayout
            header={
                <PortalHeaderBlock
                    title={`${category_label} · ${position.title}`}
                    description="Payment reference, balance, and downloadable statement."
                    actions={
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('portal.home')}>
                                All accounts
                            </Link>
                        </Button>
                    }
                />
            }
        >
            <Head title={`${category_label} — ${position.title}`} />

            <PortalPageContainer className="space-y-6">
                <PortalCompanyPaymentCard payment_info={payment_info} />

                <Card className="border-slate-200/90 shadow-sm dark:border-slate-800">
                    <CardHeader
                        className={cn(
                            'border-b border-slate-100 dark:border-slate-800',
                            category === 'loan'
                                ? 'bg-blue-50/60 dark:bg-blue-950/20'
                                : 'bg-emerald-50/60 dark:bg-emerald-950/20',
                        )}
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <span
                                className={cn(
                                    'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                    productBadge,
                                )}
                            >
                                {category_label}
                            </span>
                            {!position.is_operational ? (
                                <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                    Pending approval
                                </span>
                            ) : null}
                        </div>
                        <CardTitle className="pt-2 text-lg text-slate-900 dark:text-white">
                            Account reference & payment QR
                        </CardTitle>
                        <CardDescription>
                            Use the account number and QR together with your
                            institution’s bank details when you pay or deposit.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6 pt-6">
                        <div className="grid gap-6 sm:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Account number
                                </p>
                                <p className="mt-2 break-all font-mono text-lg font-semibold tracking-tight text-slate-900 tabular-nums dark:text-white sm:text-xl">
                                    {position.account_number}
                                </p>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Book balance
                                </p>
                                <p className="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">
                                    {moneyFromCents(position.principal_cents)}
                                </p>
                                {!position.is_operational ? (
                                    <p className="mt-2 text-xs text-amber-800 dark:text-amber-200">
                                        Provisional until this account is fully
                                        operational.
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        {qrDataUrl ? (
                            <div className="flex flex-col items-center gap-3">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Scan for this account
                                </p>
                                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-inner dark:border-slate-700">
                                    <img
                                        src={qrDataUrl}
                                        alt="Payment QR code"
                                        className="h-auto w-[min(100%,220px)]"
                                        width={220}
                                        height={220}
                                    />
                                </div>
                            </div>
                        ) : null}

                        <div>
                            <p className="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Encoded reference (debug / manual entry)
                            </p>
                            <pre className="max-h-40 overflow-auto rounded-lg border border-slate-200 bg-slate-950/5 p-3 text-xs whitespace-pre-wrap break-all text-slate-700 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-300">
                                {qr_payload}
                            </pre>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-slate-200/90 shadow-sm dark:border-slate-800">
                    <CardHeader>
                        <CardTitle className="text-base">Statement</CardTitle>
                        <CardDescription>
                            Printable statement for this product ({letterhead.name}
                            ).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button className="w-full sm:w-auto" asChild>
                            <a
                                href={statementRoute}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Open printable statement
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </PortalPageContainer>
        </AuthenticatedLayout>
    );
}
