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
import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { useMemo } from 'react';

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

function companyContactRows(company) {
    const rows = [];
    if (company?.contact_email) {
        rows.push({
            key: 'email',
            title: 'Institution email',
            detail: company.contact_email,
            helper: 'For account, balances, and portal access.',
            icon: Mail,
            mailto: company.contact_email,
        });
    }
    if (company?.phone) {
        const tel = String(company.phone).replace(/[^\d+]/g, '');
        rows.push({
            key: 'phone',
            title: 'Phone',
            detail: company.phone,
            helper: 'Office or member services line.',
            icon: Phone,
            tel: tel || null,
        });
    }
    if (company?.address) {
        rows.push({
            key: 'address',
            title: 'Location',
            detail: company.address,
            helper: 'Registered or branch address on file.',
            icon: MapPin,
        });
    }
    rows.push({
        key: 'messages',
        title: 'In-app messages',
        detail: 'Send a note to your institution',
        helper: 'Staff can reply in the Messages tab.',
        icon: MessageCircle,
        href: route('portal.messages'),
    });
    return rows;
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
    const contactRows = useMemo(() => companyContactRows(company), [company]);
    const primaryMailto = company?.contact_email
        ? `mailto:${company.contact_email}`
        : null;

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

                <section className="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Contact Us
                            </h3>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                Contact details for{' '}
                                <span className="font-medium text-slate-800 dark:text-slate-100">
                                    {company?.name ?? 'your institution'}
                                </span>
                                . Your organization manages this information in
                                Company profile.
                            </p>
                        </div>
                        {primaryMailto ? (
                            <Button variant="outline" size="sm" asChild>
                                <a href={primaryMailto}>Email institution</a>
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('portal.messages')}>
                                    Message staff
                                </Link>
                            </Button>
                        )}
                    </div>

                    {!company?.contact_email &&
                    !company?.phone &&
                    !company?.address ? (
                        <p
                            role="status"
                            className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100"
                        >
                            Your institution has not published an email, phone,
                            or address yet. Use Messages to reach them, or ask
                            them to update Company profile.
                        </p>
                    ) : null}

                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        {contactRows.map((channel) => {
                            const Icon = channel.icon;
                            const inner = (
                                <>
                                    <span className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                        <Icon className="h-4 w-4" />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                            {channel.title}
                                        </p>
                                        <p className="whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">
                                            {channel.detail}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {channel.helper}
                                        </p>
                                        {channel.mailto ? (
                                            <a
                                                href={`mailto:${channel.mailto}`}
                                                className="mt-2 inline-block text-xs font-medium text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                                            >
                                                Send email
                                            </a>
                                        ) : null}
                                        {channel.tel ? (
                                            <a
                                                href={`tel:${channel.tel}`}
                                                className="mt-2 inline-block text-xs font-medium text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                                            >
                                                Call
                                            </a>
                                        ) : null}
                                        {channel.href ? (
                                            <Link
                                                href={channel.href}
                                                className="mt-2 inline-block text-xs font-medium text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                                            >
                                                Open messages
                                            </Link>
                                        ) : null}
                                    </div>
                                </>
                            );
                            return (
                                <div
                                    key={channel.key}
                                    className="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/50"
                                >
                                    <div className="flex items-start gap-3">
                                        {inner}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                <footer className="rounded-2xl border border-slate-200/90 bg-white px-5 py-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:px-6">
                    <div className="flex flex-col gap-4 text-sm text-slate-600 dark:text-slate-300 sm:flex-row sm:items-center sm:justify-between">
                        <p>© {new Date().getFullYear()} Ledger. All rights reserved.</p>
                        <div className="flex flex-wrap items-center gap-4">
                            <Link href={route('portal.home')} className="hover:underline">
                                Overview
                            </Link>
                            <Link href={route('portal.passbook')} className="hover:underline">
                                Passbook
                            </Link>
                            <Link href={route('portal.messages')} className="hover:underline">
                                Messages
                            </Link>
                        </div>
                    </div>
                </footer>
            </PortalPageContainer>
        </AuthenticatedLayout>
    );
}
