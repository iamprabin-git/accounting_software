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
import { moneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Building2,
    Landmark,
    MessageCircle,
    ShieldCheck,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

const REPORT_ROUTE_NAMES = {
    balance_sheet: 'reports.balance-sheet',
    profit_loss: 'reports.profit-loss',
    cash_flow: 'reports.cash-flow',
};

function formatRatioDisplay(format, value) {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '—';
    }
    if (format === 'percent') {
        return `${(value * 100).toFixed(1)}%`;
    }
    return Number(value).toFixed(2);
}

function FinancialRatiosSection({ financialRatios }) {
    const { t } = useTranslation();
    if (!financialRatios) {
        return null;
    }

    const {
        items,
        can_open_reports: canOpenReports,
        admin_company_id: adminCompanyId,
        report_route_params: reportRouteParams,
        as_of: asOf,
        pl_from: plFrom,
        pl_to: plTo,
    } = financialRatios;

    const linkParams = (refKey) => {
        const base = reportRouteParams?.[refKey] ?? {};
        const p = { ...base };
        if (adminCompanyId) {
            p.company_id = adminCompanyId;
        }
        return p;
    };

    const refLabel = (refKey) =>
        t(`dashboard.ratios.ref_${refKey}`, { defaultValue: refKey });

    const refLink = (refKey) => {
        const routeName = REPORT_ROUTE_NAMES[refKey];
        if (!routeName || !reportRouteParams?.[refKey]) {
            return null;
        }
        return route(routeName, linkParams(refKey));
    };

    return (
        <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <BarChart3 className="h-5 w-5 text-slate-600 dark:text-slate-400" />
                    <CardTitle className="text-base">
                        {t('dashboard.ratios.title')}
                    </CardTitle>
                </div>
                <CardDescription className="space-y-1">
                    <span>
                        {canOpenReports
                            ? t('dashboard.ratios.subtitle')
                            : t('dashboard.ratios.subtitlePortal')}
                    </span>
                    <span className="block text-xs text-muted-foreground">
                        {t('dashboard.ratios.periodLine', {
                            from: plFrom,
                            to: plTo,
                            asOf,
                        })}
                    </span>
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {!items?.length ? (
                    <p className="text-sm text-muted-foreground">
                        {t('dashboard.ratios.empty')}
                    </p>
                ) : (
                    <ul className="divide-y divide-border rounded-lg border border-border">
                        {items.map((row) => (
                            <li
                                key={row.key}
                                className="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div className="min-w-0 flex-1 space-y-1">
                                    <p className="text-sm font-medium leading-tight">
                                        {t(
                                            `dashboard.ratios.metrics.${row.key}.name`,
                                            { defaultValue: row.key },
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        <span className="font-medium text-foreground/80">
                                            {t('dashboard.ratios.formula')}
                                            :{' '}
                                        </span>
                                        {t(
                                            `dashboard.ratios.metrics.${row.key}.formula`,
                                            { defaultValue: '' },
                                        )}
                                    </p>
                                    <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                        {(row.report_refs ?? []).map((refKey) => {
                                            const href = refLink(refKey);
                                            return canOpenReports && href ? (
                                                <Link
                                                    key={refKey}
                                                    href={href}
                                                    className="text-primary underline-offset-4 hover:underline"
                                                >
                                                    {refLabel(refKey)}
                                                </Link>
                                            ) : (
                                                <span
                                                    key={refKey}
                                                    className="text-muted-foreground"
                                                >
                                                    {refLabel(refKey)}
                                                </span>
                                            );
                                        })}
                                    </div>
                                </div>
                                <div className="shrink-0 text-right">
                                    <p className="font-mono text-lg font-semibold tabular-nums">
                                        {formatRatioDisplay(
                                            row.format,
                                            row.value,
                                        )}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                {!canOpenReports ? (
                    <p className="text-xs text-muted-foreground">
                        {t('dashboard.ratios.reportsLocked')}
                    </p>
                ) : null}
            </CardContent>
        </Card>
    );
}

function formatShortDate(iso) {
    if (!iso) {
        return '—';
    }
    try {
        const d = new Date(iso);
        return d.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '—';
    }
}

function AuditIntegrityTrendSection({ auditIntegrityTrend }) {
    if (!auditIntegrityTrend) {
        return null;
    }
    const { points, admin_company_id: adminCompanyId } = auditIntegrityTrend;
    const trailParams = adminCompanyId ? { company_id: adminCompanyId } : {};

    return (
        <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
            <CardHeader>
                <div className="flex items-center gap-2">
                    <ShieldCheck className="h-5 w-5 text-slate-600 dark:text-slate-400" />
                    <CardTitle className="text-base">
                        Audit integrity (last 7 nights)
                    </CardTitle>
                </div>
                <CardDescription>
                    Nightly chain verification from the scheduler (dots above).
                    Manual checks are logged on the audit trail and are not part of
                    this 7-night timeline.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {!points?.length ? (
                    <p className="text-sm text-muted-foreground">
                        No nightly checks recorded yet. After the scheduler runs
                        audits:verify-integrity, results appear here.
                    </p>
                ) : (
                    <div className="overflow-x-auto pb-1">
                        <div className="flex min-w-max items-end gap-0 px-1">
                            {points.map((p, i) => (
                                <div
                                    key={`${p.created_at ?? ''}-${p.action}-${i}`}
                                    className="flex flex-1 flex-col items-center gap-2"
                                    style={{ minWidth: '4.5rem' }}
                                >
                                    <span
                                        className="h-3 w-3 shrink-0 rounded-full ring-2 ring-background"
                                        title={
                                            p.pass
                                                ? 'Pass'
                                                : 'Fail'
                                        }
                                        style={{
                                            backgroundColor: p.pass
                                                ? 'rgb(22 163 74)'
                                                : 'rgb(220 38 38)',
                                        }}
                                    />
                                    <span className="text-center text-[10px] leading-tight text-muted-foreground tabular-nums">
                                        {formatShortDate(p.created_at)}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
                            <span className="inline-flex items-center gap-1.5">
                                <span
                                    className="h-2 w-2 rounded-full bg-green-600"
                                    aria-hidden
                                />
                                Pass
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span
                                    className="h-2 w-2 rounded-full bg-red-600"
                                    aria-hidden
                                />
                                Fail
                            </span>
                        </div>
                    </div>
                )}
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={() =>
                            router.post(
                                route('audit-trail.verify-now', trailParams),
                            )
                        }
                    >
                        Verify now
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('audit-trail.index', trailParams)}>
                            Open audit trail
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function ControlCenterSection({ controlCenter }) {
    const page = usePage();
    if (!controlCenter) {
        return null;
    }

    const companyId = controlCenter.admin_company_id ?? undefined;
    const q = companyId ? { company_id: companyId } : {};
    const teller = controlCenter.teller ?? {};
    const pending = controlCenter.pending ?? {};

    return (
        <Card className="cbs-surface border-indigo-200/70 bg-indigo-50/40 dark:border-indigo-900/40 dark:bg-indigo-950/20">
            <CardHeader>
                <CardTitle>Control Center</CardTitle>
                <CardDescription>
                    Daily controls, pending approvals, and teller readiness.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-3 sm:grid-cols-4">
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-xs text-muted-foreground">
                            Total pending
                        </p>
                        <p className="text-xl font-semibold">
                            {pending.total ?? 0}
                        </p>
                    </div>
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-xs text-muted-foreground">Members</p>
                        <p className="text-xl font-semibold">
                            {pending.members ?? 0}
                        </p>
                    </div>
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-xs text-muted-foreground">
                            Chart accounts
                        </p>
                        <p className="text-xl font-semibold">
                            {pending.chart_accounts ?? 0}
                        </p>
                    </div>
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-xs text-muted-foreground">
                            Journal approvals
                        </p>
                        <p className="text-xl font-semibold">
                            {pending.journals ?? 0}
                        </p>
                    </div>
                </div>

                <div className="rounded-md border bg-background/70 p-3">
                    <p className="text-sm font-medium">Teller day (today)</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Status:{' '}
                        <span className="font-medium text-foreground">
                            {teller.is_open ? 'Open' : 'Not started'}
                        </span>
                        {teller.close_date ? ` · ${teller.close_date}` : ''}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        System cash: {moneyFromCents(teller.system_cash_cents || 0)}
                        {' · '}Cash received:{' '}
                        {moneyFromCents(teller.cash_received_cents || 0)}
                        {' · '}Variance:{' '}
                        {moneyFromCents(teller.closing_error_cents || 0)}
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('members.index', q)}>
                            Review members
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('chart-accounts.index', q)}>
                            Review chart accounts
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('journals.index', q)}>
                            Review journals
                        </Link>
                    </Button>
                    {page.props.auth.user?.role === 'company' ? (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('company.team.index')}>
                                Manual review login users
                            </Link>
                        </Button>
                    ) : null}
                    {page.props.auth.user?.can_edit_accounting ? (
                        <Button size="sm" asChild>
                            <Link href={route('teller.day-close.create', q)}>
                                Open teller controls
                            </Link>
                        </Button>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    );
}

function SystemHealthSection({ systemHealth }) {
    if (!systemHealth) {
        return null;
    }

    const tb = systemHealth.trial_balance ?? {};
    const lock = systemHealth.period_lock ?? {};
    const companyId = systemHealth.admin_company_id ?? undefined;
    const q = companyId ? { company_id: companyId } : {};

    return (
        <Card className="cbs-surface border-emerald-200/70 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <CardHeader>
                <CardTitle>System Health Checks</CardTitle>
                <CardDescription>
                    Trial balance integrity and accounting period lock posture.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-sm font-medium">Trial balance check</p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Debit: {moneyFromCents(tb.debit_cents || 0)} · Credit:{' '}
                            {moneyFromCents(tb.credit_cents || 0)}
                        </p>
                        <p
                            className={`mt-1 text-xs font-medium ${
                                tb.is_balanced
                                    ? 'text-emerald-700'
                                    : 'text-red-700'
                            }`}
                        >
                            {tb.is_balanced
                                ? 'Balanced'
                                : `Out of balance by ${moneyFromCents(
                                      Math.abs(tb.delta_cents || 0),
                                  )}`}
                        </p>
                    </div>
                    <div className="rounded-md border bg-background/70 p-3">
                        <p className="text-sm font-medium">Accounting period lock</p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {lock.is_set
                                ? `Locked through ${lock.lock_date}`
                                : 'No lock date set'}
                            {lock.last_close_type
                                ? ` · Last close: ${lock.last_close_type}`
                                : ''}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {lock.lock_reason || 'No lock reason recorded'}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('reports.trial-balance', q)}>
                            Open trial balance
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('reports.balance-sheet', q)}>
                            Open balance sheet
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('company.profile.edit', q)}>
                            Period settings
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function ApprovalInboxSection({ approvalInbox }) {
    if (!approvalInbox) {
        return null;
    }

    const companyId = approvalInbox.admin_company_id ?? undefined;
    const q = companyId ? { company_id: companyId } : {};

    const List = ({ title, rows = [], href }) => (
        <div className="rounded-md border bg-background/70 p-3">
            <div className="mb-2 flex items-center justify-between">
                <p className="text-sm font-medium">{title}</p>
                <Button variant="ghost" size="sm" asChild>
                    <Link href={href}>Open</Link>
                </Button>
            </div>
            {rows.length === 0 ? (
                <p className="text-xs text-muted-foreground">No pending items.</p>
            ) : (
                <ul className="space-y-1">
                    {rows.map((r) => (
                        <li
                            key={`${title}-${r.id}`}
                            className="text-xs text-muted-foreground"
                        >
                            <span className="font-medium text-foreground">
                                {r.label}
                            </span>{' '}
                            · {formatShortDate(r.created_at)}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );

    return (
        <Card className="cbs-surface border-violet-200/70 bg-violet-50/40 dark:border-violet-900/40 dark:bg-violet-950/20">
            <CardHeader>
                <CardTitle>Approval Inbox</CardTitle>
                <CardDescription>
                    Centralized queue of oldest pending approvals across modules.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3 md:grid-cols-3">
                <List
                    title="Members"
                    rows={approvalInbox.members}
                    href={route('members.index', q)}
                />
                <List
                    title="Chart accounts"
                    rows={approvalInbox.chart_accounts}
                    href={route('chart-accounts.index', q)}
                />
                <List
                    title="Journals"
                    rows={approvalInbox.journals}
                    href={route('journals.index', q)}
                />
            </CardContent>
        </Card>
    );
}

function portalHint(state, t) {
    if (state === 'ok') {
        return null;
    }
    return t(`dashboard.hint_${state}`);
}

export default function Dashboard({
    stats,
    readOnly,
    endUserPortal,
    financialRatios,
    approvalSla,
    auditIntegrityAlert,
    auditIntegrityTrend,
    controlCenter,
    systemHealth,
    approvalInbox,
}) {
    const { t } = useTranslation();
    const page = usePage();
    const user = page.props.auth.user;
    const isEndUser = user?.role === 'end_user';
    const companyFeatures = page.props.company_features;

    if (isEndUser && endUserPortal) {
        const { state, can_view_finance } = endUserPortal;
        const hint = portalHint(state, t);
        const companyName = user?.company?.name ?? '—';

        if (!companyFeatures?.members) {
            return (
                <AuthenticatedLayout
                    header={
                        <PortalHeaderBlock
                            title={t('dashboard.titleEndUser')}
                            description={t('dashboard.signedInCompany', {
                                name: companyName,
                            })}
                        />
                    }
                >
                    <Head title={t('nav.dashboard')} />
                    <PortalPageContainer className="space-y-6">
                        <div
                            role="status"
                            className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-800 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200"
                        >
                            {t('dashboard.memberPortalDisabled')}
                        </div>
                    </PortalPageContainer>
                </AuthenticatedLayout>
            );
        }

        return (
            <AuthenticatedLayout
                header={
                    <PortalHeaderBlock
                        title={t('dashboard.titleEndUser')}
                        description={t('dashboard.signedInCompany', {
                            name: companyName,
                        })}
                    />
                }
            >
                <Head title={t('nav.dashboard')} />

                <PortalPageContainer className="space-y-6">
                    {hint ? (
                        <div
                            role="status"
                            className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-relaxed text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                        >
                            {hint}
                        </div>
                    ) : null}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
                            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                                <div>
                                    <CardTitle className="text-base">
                                        {t('dashboard.myLoansSavings')}
                                    </CardTitle>
                                    <Landmark className="mt-2 h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <CardDescription>
                                    {can_view_finance
                                        ? t('dashboard.myLoansDesc')
                                        : t('dashboard.myLoansPending')}
                                </CardDescription>
                                <Button className="w-full" variant="secondary" asChild>
                                    <Link href={route('portal.home')}>
                                        {t('dashboard.openAccounts')}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                        <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
                            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                                <div>
                                    <CardTitle className="text-base">
                                        {t('dashboard.passbookCard')}
                                    </CardTitle>
                                    <BookOpen className="mt-2 h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <CardDescription>
                                    {t('dashboard.passbookDesc')}
                                </CardDescription>
                                {can_view_finance ? (
                                    <Button className="w-full" variant="outline" asChild>
                                        <Link href={route('portal.passbook')}>
                                            {t('dashboard.viewPassbook')}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button className="w-full" variant="outline" disabled>
                                        {t('dashboard.viewPassbook')}
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <FinancialRatiosSection financialRatios={financialRatios} />

                    <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <MessageCircle className="h-5 w-5 text-slate-600 dark:text-slate-400" />
                                <CardTitle className="text-base">
                                    {t('dashboard.messages')}
                                </CardTitle>
                            </div>
                            <CardDescription>
                                {t('dashboard.messagesDesc')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <Button className="w-full sm:w-auto" asChild>
                                <Link href={route('portal.messages')}>
                                    {t('dashboard.openMessages')}
                                </Link>
                            </Button>
                            <Button
                                className="w-full sm:w-auto"
                                variant="outline"
                                asChild
                            >
                                <Link href={route('profile.edit')}>
                                    {t('dashboard.profileSettings')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </PortalPageContainer>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    {t('dashboard.title')}
                </h2>
            }
        >
            <Head title={t('nav.dashboard')} />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    {auditIntegrityAlert ? (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-100">
                            <p className="font-semibold">
                                Audit integrity alert
                            </p>
                            <p className="mt-1">
                                Last check failed (
                                {auditIntegrityAlert.reason || 'unknown'}). First
                                broken event:{' '}
                                {auditIntegrityAlert.first_broken_event_id ??
                                    'n/a'}
                                .
                            </p>
                            <div className="mt-2">
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={route('audit-trail.index', {
                                            company_id:
                                                auditIntegrityAlert.admin_company_id ??
                                                undefined,
                                        })}
                                    >
                                        Open audit trail
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ) : null}
                    {readOnly && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
                            {t('dashboard.readOnly')}
                        </div>
                    )}
                    {companyFeatures?.core_banking_professional &&
                    user.can_edit_accounting ? (
                        <Card className="cbs-surface border-indigo-200/80 bg-gradient-to-r from-indigo-50/90 to-white dark:border-indigo-900/40 dark:from-indigo-950/50 dark:to-slate-950">
                            <CardHeader className="flex flex-row items-start gap-3 space-y-0">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm">
                                    <Building2 className="h-5 w-5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <CardTitle className="text-base">
                                        Core banking (professional)
                                    </CardTitle>
                                    <CardDescription className="mt-1">
                                        Unified hub for members, deposits, loans,
                                        group operations, journals, and treasury.
                                    </CardDescription>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <Button asChild>
                                    <Link
                                        href={route(
                                            'banking.operations',
                                            user.role === 'admin' &&
                                                page.props.current_company_id
                                                ? {
                                                      company_id:
                                                          page.props
                                                              .current_company_id,
                                                  }
                                                : {},
                                        )}
                                    >
                                        Open operations hub
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ) : null}

                    <div className="grid gap-4 md:grid-cols-2">
                        <Card className="cbs-surface">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {t('dashboard.chartAccounts')}
                                </CardTitle>
                                <Landmark className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {stats.accounts}
                                </div>
                                <CardDescription>
                                    {t('dashboard.chartAccountsDesc')}
                                </CardDescription>
                            </CardContent>
                        </Card>
                        <Card className="cbs-surface">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {t('dashboard.journalEntries')}
                                </CardTitle>
                                <BookOpen className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {stats.journal_entries}
                                </div>
                                <CardDescription>
                                    {t('dashboard.journalEntriesDesc')}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </div>

                    <FinancialRatiosSection financialRatios={financialRatios} />
                    <ControlCenterSection controlCenter={controlCenter} />
                    <SystemHealthSection systemHealth={systemHealth} />
                    <ApprovalInboxSection approvalInbox={approvalInbox} />

                    {approvalSla ? (
                        <Card className="cbs-surface">
                            <CardHeader>
                                <CardTitle>Approval SLA</CardTitle>
                                <CardDescription>
                                    Pending journal approvals aging by SLA buckets.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-3 sm:grid-cols-3">
                                    <div className="rounded-md border p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Pending total
                                        </p>
                                        <p className="text-xl font-semibold">
                                            {approvalSla.pending_total}
                                        </p>
                                    </div>
                                    <div className="rounded-md border p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Over 2 days
                                        </p>
                                        <p className="text-xl font-semibold">
                                            {approvalSla.over_2_days}
                                        </p>
                                    </div>
                                    <div className="rounded-md border p-3">
                                        <p className="text-xs text-muted-foreground">
                                            Over 7 days
                                        </p>
                                        <p className="text-xl font-semibold">
                                            {approvalSla.over_7_days}
                                        </p>
                                    </div>
                                </div>
                                {approvalSla.oldest_pending ? (
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-sm font-medium">
                                            Oldest pending: Journal #
                                            {approvalSla.oldest_pending.id}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Age:{' '}
                                            {approvalSla.oldest_pending
                                                .pending_age_days ?? 0}{' '}
                                            day(s)
                                            {approvalSla.oldest_pending
                                                .first_approved_by_name
                                                ? ` · First approved by ${approvalSla.oldest_pending.first_approved_by_name}`
                                                : ''}
                                        </p>
                                        <div className="mt-2 flex gap-2">
                                            <Button variant="outline" asChild>
                                                <Link
                                                    href={route('journals.show', {
                                                        journal: approvalSla
                                                            .oldest_pending.id,
                                                        company_id:
                                                            approvalSla.admin_company_id ??
                                                            undefined,
                                                    })}
                                                >
                                                    Review oldest
                                                </Link>
                                            </Button>
                                            <Button variant="outline" asChild>
                                                <Link
                                                    href={route('journals.index', {
                                                        company_id:
                                                            approvalSla.admin_company_id ??
                                                            undefined,
                                                    })}
                                                >
                                                    Open journals
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No pending journals right now.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ) : null}

                    <AuditIntegrityTrendSection
                        auditIntegrityTrend={auditIntegrityTrend}
                    />

                    <Card className="cbs-surface">
                        <CardHeader>
                            <CardTitle>{t('dashboard.nextSteps')}</CardTitle>
                            <CardDescription>
                                {t('dashboard.nextStepsDesc')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-3">
                            {!readOnly && (
                                <>
                                    {page.props.auth.user
                                        ?.can_manage_chart_of_accounts && (
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={route(
                                                    'chart-accounts.index',
                                                )}
                                            >
                                                {t('dashboard.chartAccounts')}
                                            </Link>
                                        </Button>
                                    )}
                                    <Button variant="outline" asChild>
                                        <Link href={route('journals.index')}>
                                            {t('dashboard.journalEntries')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link href={route('reports.index')}>
                                            {t('dashboard.financialReports')}
                                        </Link>
                                    </Button>
                                </>
                            )}
                            {readOnly && (
                                <>
                                    <Button variant="outline" asChild>
                                        <Link href={route('journals.index')}>
                                            {t('dashboard.viewJournals')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link href={route('reports.index')}>
                                            {t('dashboard.viewReports')}
                                        </Link>
                                    </Button>
                                </>
                            )}
                            <Button variant="secondary" asChild>
                                <Link href={route('profile.edit')}>
                                    {t('dashboard.profileSettings')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
