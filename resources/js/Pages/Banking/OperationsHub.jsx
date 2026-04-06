import CompanyPicker from '@/Components/CompanyPicker';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    Building2,
    Calculator,
    ClipboardList,
    Landmark,
    Layers,
    PiggyBank,
    Scale,
    Shield,
    Users,
} from 'lucide-react';

function money(cents) {
    return (Number(cents || 0) / 100).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function StatCard({ title, value, sub, icon: Icon }) {
    return (
        <Card className="border-slate-200/90 shadow-sm transition-shadow hover:shadow-md dark:border-slate-800">
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                {Icon ? (
                    <Icon className="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" />
                ) : null}
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-semibold tabular-nums tracking-tight">
                    {value}
                </p>
                {sub ? (
                    <p className="mt-1 text-xs text-muted-foreground">{sub}</p>
                ) : null}
            </CardContent>
        </Card>
    );
}

function QuickLink({ href, title, desc }) {
    return (
        <Link
            href={href}
            className="group flex items-start justify-between gap-3 rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-indigo-800"
        >
            <div>
                <p className="font-medium text-slate-900 dark:text-slate-100">
                    {title}
                </p>
                <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
            </div>
            <ArrowRight className="mt-0.5 h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-indigo-600 dark:group-hover:text-indigo-400" />
        </Link>
    );
}

export default function OperationsHub({ stats, companies, currentCompanyId }) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const isAdmin = user.role === 'admin';
    const q = isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    const fin = (cat, ws) =>
        route('finance.positions.index', { category: cat, workspace: ws, ...q });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-white shadow">
                            <Building2 className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="text-xl font-semibold text-foreground">
                                Core banking — operations hub
                            </h2>
                            <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                                Professional mode: single entry point for member
                                relationships, deposit &amp; loan portfolios, group
                                banking, general ledger activity, and treasury
                                controls. All figures are live for the selected
                                institution.
                            </p>
                        </div>
                    </div>
                    {companies?.length > 0 && (
                        <CompanyPicker
                            companies={companies}
                            currentCompanyId={currentCompanyId}
                            routeName="banking.operations"
                            routeParams={{}}
                            query={{}}
                        />
                    )}
                </div>
            }
        >
            <Head title="Core banking" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-10 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            title="Approved members"
                            value={stats.members_approved}
                            sub="Ready for finance linking"
                            icon={Users}
                        />
                        <StatCard
                            title="Loan accounts"
                            value={stats.loan_accounts}
                            sub={`Outstanding ${money(stats.loan_outstanding_cents)}`}
                            icon={Landmark}
                        />
                        <StatCard
                            title="Savings accounts"
                            value={stats.savings_accounts}
                            sub={`Balances ${money(stats.savings_principal_cents)}`}
                            icon={PiggyBank}
                        />
                        <StatCard
                            title="Pending journals"
                            value={stats.pending_journals}
                            sub={`${stats.member_groups} member groups`}
                            icon={ClipboardList}
                        />
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/40">
                        <h3 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                            Integration API (read-only)
                        </h3>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Issue a Bearer token, then call JSON endpoints. Platform
                            admins must pass{' '}
                            <code className="rounded bg-slate-200 px-1 py-0.5 text-xs dark:bg-slate-800">
                                company_id
                            </code>{' '}
                            on every banking request.
                        </p>
                        <ul className="mt-3 space-y-1 font-mono text-xs text-slate-700 dark:text-slate-300">
                            <li>
                                <span className="text-emerald-700 dark:text-emerald-400">
                                    POST
                                </span>{' '}
                                /api/v1/auth/token (body: scopes[])
                            </li>
                            <li>
                                <span className="text-rose-700 dark:text-rose-400">DEL</span>{' '}
                                /api/v1/auth/tokens/current
                            </li>
                            <li>
                                <span className="text-sky-700 dark:text-sky-400">GET</span>{' '}
                                /api/v1/banking/summary
                            </li>
                            <li>
                                <span className="text-sky-700 dark:text-sky-400">GET</span>{' '}
                                /api/v1/banking/members
                            </li>
                            <li>
                                <span className="text-sky-700 dark:text-sky-400">GET</span>{' '}
                                /api/v1/banking/positions?category=loan|savings
                            </li>
                            <li>
                                <span className="text-emerald-700 dark:text-emerald-400">
                                    POST
                                </span>{' '}
                                /api/v1/banking/journals/two-line
                            </li>
                            <li>
                                <span className="text-emerald-700 dark:text-emerald-400">
                                    POST
                                </span>{' '}
                                /api/v1/banking/transfers
                            </li>
                            <li>
                                <span className="text-emerald-700 dark:text-emerald-400">
                                    POST
                                </span>{' '}
                                /api/v1/banking/webhooks
                            </li>
                        </ul>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Scopes:{' '}
                            <code className="rounded bg-slate-200 px-1 dark:bg-slate-800">
                                banking:read
                            </code>
                            ,{' '}
                            <code className="rounded bg-slate-200 px-1 dark:bg-slate-800">
                                banking:journal
                            </code>
                            ,{' '}
                            <code className="rounded bg-slate-200 px-1 dark:bg-slate-800">
                                banking:webhooks:manage
                            </code>
                            . UI: Company profile → Integrations &amp; API.
                        </p>
                        <p className="mt-3 text-xs text-muted-foreground">
                            Header:{' '}
                            <code className="rounded bg-slate-200 px-1 py-0.5 dark:bg-slate-800">
                                Authorization: Bearer {'<token>'}
                            </code>
                        </p>
                    </div>

                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-2">
                            <h3 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                Operations
                            </h3>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <QuickLink
                                    href={route('members.index', q)}
                                    title="Members"
                                    desc="Onboarding, approval, member ledger"
                                />
                                <QuickLink
                                    href={route('member-groups.index', q)}
                                    title="Member groups"
                                    desc="Group savings deposits &amp; loan collection sheets"
                                />
                                <QuickLink
                                    href={fin('loan', 'front')}
                                    title="Loans — front desk"
                                    desc="Disbursements, installments, penalties"
                                />
                                <QuickLink
                                    href={fin('savings', 'front')}
                                    title="Savings — front desk"
                                    desc="Deposits, withdrawals, statements"
                                />
                                <QuickLink
                                    href={fin('loan', 'back')}
                                    title="Loans — back office"
                                    desc="Approvals, products, structural edits"
                                />
                                <QuickLink
                                    href={fin('savings', 'back')}
                                    title="Savings — back office"
                                    desc="Approvals, products, adjustments"
                                />
                                <QuickLink
                                    href={route('finance.account-entry', q)}
                                    title="Account number entry"
                                    desc="Fast lookup for teller-style service"
                                />
                                <QuickLink
                                    href={route('teller.day-close.create', q)}
                                    title="Teller day close"
                                    desc="Cash count &amp; daily control sheet"
                                />
                                <QuickLink
                                    href={route('journals.index', q)}
                                    title="Journals"
                                    desc="Draft, submit, approve, reverse"
                                />
                                <QuickLink
                                    href={route('bank-reconciliation.index', q)}
                                    title="Bank reconciliation"
                                    desc="Statements, matching, exceptions"
                                />
                                <QuickLink
                                    href={route('reports.par-aging', q)}
                                    title="PAR / loan aging"
                                    desc="Portfolio buckets &amp; concentration"
                                />
                                <QuickLink
                                    href={route('reports.index', q)}
                                    title="Financial reports"
                                    desc="Trial balance, P&amp;L, balance sheet, GL"
                                />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <h3 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                Control &amp; compliance
                            </h3>
                            <Card className="border-slate-200/90 dark:border-slate-800">
                                <CardHeader>
                                    <div className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                        <CardTitle className="text-base">
                                            Built-in controls
                                        </CardTitle>
                                    </div>
                                    <CardDescription className="text-xs leading-relaxed">
                                        Dual approval thresholds, journal period
                                        locks, immutable audit trail with integrity
                                        checks, and finance positions tied to
                                        approved chart accounts.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm text-muted-foreground">
                                    <div className="flex gap-2">
                                        <Scale className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>
                                            General ledger, COA approval workflow,
                                            and structured loan / savings products.
                                        </span>
                                    </div>
                                    <div className="flex gap-2">
                                        <Calculator className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>
                                            Accruals, movements, and journal-linked
                                            principal balances.
                                        </span>
                                    </div>
                                    <div className="flex gap-2">
                                        <Layers className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>
                                            Group batch posting for savings and
                                            multi-component loan collections.
                                        </span>
                                    </div>
                                    <div className="flex gap-2">
                                        <Banknote className="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>
                                            Cash journals, teller close, and bank
                                            feed / CSV reconciliation.
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
