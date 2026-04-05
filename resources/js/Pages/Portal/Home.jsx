import PortalAccountCard from '@/Components/Portal/PortalAccountCard';
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
import { Head, Link } from '@inertiajs/react';

function stateMessage(portalState) {
    switch (portalState) {
        case 'no_member':
            return 'No member profile is linked to your login email yet. Ask your company to register you as a member using the same email address you use to sign in.';
        case 'member_pending':
            return 'Your member registration is waiting for company approval. You will see your accounts here once you are approved.';
        case 'member_rejected':
            return 'Your member registration was not approved. Please contact your company for details.';
        case 'portal_pending':
            return 'Your company has not yet approved access to the customer portal. You can still send messages from the Messages tab; loans and savings will appear after approval.';
        default:
            return null;
    }
}

export default function Home({
    payment_info,
    portal_state,
    member,
    company,
    loans,
    savings,
}) {
    const blocked = portal_state !== 'ok';

    return (
        <AuthenticatedLayout
            header={
                <PortalHeaderBlock
                    title="Accounts overview"
                    description={
                        company?.name
                            ? `Products and balances on file with ${company.name}.`
                            : 'Your loan and savings relationships in one place.'
                    }
                    actions={
                        !blocked ? (
                            <div className="flex w-full flex-col gap-2 min-[400px]:w-auto min-[400px]:flex-row">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('portal.passbook')}>
                                        Passbook
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('portal.messages')}>
                                        Messages
                                    </Link>
                                </Button>
                            </div>
                        ) : null
                    }
                />
            }
        >
            <Head title="My accounts" />

            <PortalPageContainer className="space-y-6">
                {company?.name ? (
                    <div className="flex flex-col gap-1 rounded-xl border border-slate-200/90 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            <p className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Institution
                            </p>
                            <p className="text-lg font-semibold text-slate-900 dark:text-white">
                                {company.name}
                            </p>
                        </div>
                    </div>
                ) : null}

                <PortalCompanyPaymentCard payment_info={payment_info} />

                {blocked && (
                    <div
                        role="status"
                        className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-relaxed text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                    >
                        {stateMessage(portal_state)}
                    </div>
                )}

                {member ? (
                    <Card className="border-slate-200/90 shadow-sm dark:border-slate-800">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                Member record
                            </CardTitle>
                            <CardDescription>
                                Matched to your login email.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50">
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Name
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-medium text-slate-900 dark:text-white">
                                        {member.name}
                                    </dd>
                                </div>
                                <div className="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50">
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Member no.
                                    </dt>
                                    <dd className="mt-0.5 font-mono text-sm font-medium text-slate-900 dark:text-white">
                                        #{member.member_number}
                                    </dd>
                                </div>
                                <div className="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50">
                                    <dt className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Status
                                    </dt>
                                    <dd className="mt-0.5 text-sm font-medium capitalize text-slate-900 dark:text-white">
                                        {member.status}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                ) : null}

                {!blocked && (
                    <div className="space-y-8">
                        <section className="space-y-3">
                            <div className="flex items-end justify-between gap-4 border-b border-slate-200 pb-2 dark:border-slate-800">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Loan accounts
                                </h3>
                            </div>
                            {loans.length === 0 ? (
                                <p className="rounded-lg border border-dashed border-slate-200 bg-white/50 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400">
                                    No loan accounts on file.
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {loans.map((row) => (
                                        <li key={row.id}>
                                            <PortalAccountCard
                                                variant="loan"
                                                title={row.title}
                                                accountNumber={row.account_number}
                                                principalCents={row.principal_cents}
                                                isOperational={row.is_operational}
                                                href={route(
                                                    'portal.positions.show',
                                                    {
                                                        category: 'loan',
                                                        position: row.id,
                                                    },
                                                )}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-3">
                            <div className="flex items-end justify-between gap-4 border-b border-slate-200 pb-2 dark:border-slate-800">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Savings accounts
                                </h3>
                            </div>
                            {savings.length === 0 ? (
                                <p className="rounded-lg border border-dashed border-slate-200 bg-white/50 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400">
                                    No savings accounts on file.
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {savings.map((row) => (
                                        <li key={row.id}>
                                            <PortalAccountCard
                                                variant="savings"
                                                title={row.title}
                                                accountNumber={row.account_number}
                                                principalCents={row.principal_cents}
                                                isOperational={row.is_operational}
                                                href={route(
                                                    'portal.positions.show',
                                                    {
                                                        category: 'savings',
                                                        position: row.id,
                                                    },
                                                )}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                )}
            </PortalPageContainer>
        </AuthenticatedLayout>
    );
}
