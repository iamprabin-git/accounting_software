import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { cn } from '@/lib/utils';

/**
 * @param {{ payment_info?: { visible: boolean, bank_payment_details: string|null, payment_qr_url: string|null } }} props
 */
export default function PortalCompanyPaymentCard({ payment_info, className }) {
    if (!payment_info?.visible) {
        return null;
    }

    const bank = (payment_info.bank_payment_details || '').trim();
    const qrUrl = payment_info.payment_qr_url;

    return (
        <Card
            className={cn(
                'overflow-hidden border-slate-200/90 shadow-sm dark:border-slate-800',
                'border-l-4 border-l-emerald-600 dark:border-l-emerald-500',
                className,
            )}
        >
            <CardHeader className="bg-emerald-50/80 pb-4 dark:bg-emerald-950/30">
                <CardTitle className="text-base font-bold text-slate-900 dark:text-white">
                    Payment instructions
                </CardTitle>
                <CardDescription className="text-slate-600 dark:text-slate-400">
                    Official bank details and QR from your institution—use for
                    loan repayments and savings deposits. Your cooperative shows
                    this where your administrator enables it (dashboard,
                    accounts, passbook, messages, and account statement).
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 pt-6">
                {bank ? (
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Bank & transfer details
                        </p>
                        <div className="mt-2 rounded-lg border border-slate-200 bg-slate-50/80 p-4 text-sm leading-relaxed whitespace-pre-wrap text-slate-800 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200">
                            {bank}
                        </div>
                    </div>
                ) : null}
                {qrUrl ? (
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Scan to pay
                        </p>
                        <div className="mt-2 inline-flex rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700">
                            <img
                                src={qrUrl}
                                alt="Company payment QR code"
                                className="mx-auto max-h-52 w-auto max-w-full object-contain sm:max-h-56"
                                width={220}
                                height={220}
                            />
                        </div>
                    </div>
                ) : null}
                {!bank && !qrUrl ? (
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Your company enabled this section but has not added
                        instructions or a QR image yet.
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}
