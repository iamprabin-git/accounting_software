import ApplicationLogo from '@/Components/ApplicationLogo';
import PortalBottomNav from '@/Components/Portal/PortalBottomNav';
import ThemeLanguageControls from '@/Components/ThemeLanguageControls';
import Dropdown from '@/Components/Dropdown';
import SidebarNavLink from '@/Components/SidebarNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

function buildNavSections(
    user,
    pageUrl = '',
    t,
    companyFeatures,
    currentCompanyId,
) {
    const cbsRank = (href = '') => {
        if (href.includes('/members')) return 10;
        if (href.includes('/member-groups')) return 20;
        if (href.includes('/finance/loan-products')) return 30;
        if (href.includes('/finance/savings-products')) return 40;
        if (href.includes('/workspace/front-desk')) return 50;
        if (href.includes('/workspace/back-office')) return 60;
        if (href.includes('/banking/operations')) return 70;
        if (href.includes('/finance/account-entry')) return 80;
        if (href.includes('/finance/loan') && href.includes('workspace=front')) return 90;
        if (href.includes('/finance/savings') && href.includes('workspace=front')) return 100;
        if (href.includes('/finance/loan') && href.includes('workspace=back')) return 110;
        if (href.includes('/finance/savings') && href.includes('workspace=back')) return 120;
        if (href.includes('/finance/investment')) return 130;
        if (href.includes('/chart-accounts')) return 140;
        return 999;
    };

    const cf = companyFeatures ?? {
        plan: 'starter',
        inventory: false,
        members: false,
        debtors_creditors: false,
        finance: false,
    };

    const sections = [];
    const cbsItems = [];
    const opsItems = [];

    const finUrl = (cat, ws) =>
        route('finance.positions.index', { category: cat, workspace: ws });

    const mainItems = [
        {
            href: route('dashboard'),
            label: t('nav.dashboard'),
            active: route().current('dashboard'),
        },
    ];
    if (user.role === 'admin' && user.can_view_reports) {
        mainItems.push({
            href: route('company.integrations.index'),
            label: 'Integrations & API',
            active: route().current('company.integrations.*'),
        });
    }
    sections.push({
        id: 'main',
        title: t('nav.main'),
        items: mainItems,
    });

    if (user.role === 'end_user') {
        if (cf.members) {
            sections.push({
                id: 'my_accounts',
                title: t('nav.myAccounts'),
                items: [
                    {
                        href: route('portal.home'),
                        label: t('nav.loansSavings'),
                        active: route().current('portal.home'),
                    },
                    {
                        href: route('portal.passbook'),
                        label: t('nav.passbook'),
                        active: route().current('portal.passbook'),
                    },
                    {
                        href: route('portal.messages'),
                        label: t('nav.messagesCompany'),
                        active: pageUrl.startsWith('/portal/messages'),
                    },
                ],
            });
        }
        return sections;
    }

    if (user.can_edit_accounting && cf.finance) {
        cbsItems.push(
            {
                href: route('workspace.front-desk'),
                label: t('nav.frontDesk'),
                active: route().current('workspace.front-desk'),
            },
            {
                href: route('workspace.back-office'),
                label: t('nav.backOffice'),
                active: route().current('workspace.back-office'),
            },
            {
                href: route('finance.account-entry'),
                label: t('nav.accountNumberEntry'),
                active: route().current('finance.account-entry'),
            },
        );
    }

    if (
        user.can_edit_accounting &&
        cf.members &&
        cf.finance &&
        cf.core_banking_professional
    ) {
        const bankingQ =
            user.role === 'admin' && currentCompanyId
                ? { company_id: currentCompanyId }
                : {};
        cbsItems.push({
            href: route('banking.operations', bankingQ),
            label: 'Operations hub',
            active: route().current('banking.operations'),
        });
    }

    if (user.role === 'company') {
        const companyItems = [
            {
                href: route('company.profile.edit'),
                label: t('nav.companyProfile'),
                active: route().current('company.profile.*'),
            },
        ];
        if (user.can_manage_team) {
            companyItems.push({
                href: route('company.team.index'),
                label: t('nav.team'),
                active: route().current('company.team.*'),
            });
        }
        if (cf.members) {
            companyItems.push({
                href: route('company.customer-chat.index'),
                label: t('nav.customerMessages'),
                active: route().current('company.customer-chat.*'),
            });
        }
        sections.push({ id: 'company', title: t('nav.company'), items: companyItems });
    } else if (user.can_manage_team) {
        sections.push({
            id: 'organization',
            title: t('nav.organization'),
            items: [
                {
                    href: route('company.team.index'),
                    label: t('nav.team'),
                    active: route().current('company.team.*'),
                },
            ],
        });
    }

    if (user.role === 'staff' && cf.members) {
        sections.push({
            id: 'customers',
            title: t('nav.customers'),
            items: [
                {
                    href: route('company.customer-chat.index'),
                    label: t('nav.customerMessages'),
                    active: route().current('company.customer-chat.*'),
                },
            ],
        });
    }

    if (user.can_manage_chart_of_accounts) {
        cbsItems.push({
            href: route('chart-accounts.index'),
            label: t('nav.chartOfAccounts'),
            active: route().current('chart-accounts.*'),
        });
    }

    if (user.can_edit_accounting) {
        const items = [];
        if (cf.inventory) {
            opsItems.push({
                href: route('inventory.index'),
                label: t('nav.inventory'),
                active: route().current('inventory.*'),
            });
        }
        if (cf.debtors_creditors) {
            opsItems.push(
                {
                    href: route('debtors.index'),
                    label: t('nav.debtors'),
                    active: route().current('debtors.*'),
                },
                {
                    href: route('creditors.index'),
                    label: t('nav.creditors'),
                    active: route().current('creditors.*'),
                },
            );
        }
        if (cf.members) {
            items.push(
                {
                    href: route('members.index'),
                    label: t('nav.members'),
                    active: route().current('members.*'),
                },
                {
                    href: route('member-groups.index'),
                    label: 'Member groups',
                    active: route().current('member-groups.*'),
                },
            );
        }
        if (cf.finance) {
            items.push(
                {
                    href: finUrl('loan', 'front'),
                    label: t('nav.loansFront'),
                    active:
                        route().current('finance.positions.*') &&
                        pageUrl.includes('finance/loan') &&
                        pageUrl.includes('workspace=front'),
                },
                {
                    href: finUrl('savings', 'front'),
                    label: t('nav.savingsFront'),
                    active:
                        route().current('finance.positions.*') &&
                        pageUrl.includes('finance/savings') &&
                        pageUrl.includes('workspace=front'),
                },
                {
                    href: finUrl('loan', 'back'),
                    label: t('nav.loansBack'),
                    active:
                        route().current('finance.positions.*') &&
                        pageUrl.includes('finance/loan') &&
                        pageUrl.includes('workspace=back'),
                },
                {
                    href: finUrl('savings', 'back'),
                    label: t('nav.savingsBack'),
                    active:
                        route().current('finance.positions.*') &&
                        pageUrl.includes('finance/savings') &&
                        pageUrl.includes('workspace=back'),
                },
                {
                    href: route('finance.positions.index', {
                        category: 'investment',
                        workspace: 'back',
                    }),
                    label: t('nav.investments'),
                    active:
                        route().current('finance.positions.*') &&
                        pageUrl.includes('finance/investment'),
                },
                {
                    href: route('finance.loan-products.index'),
                    label: t('nav.loanProducts'),
                    active: route().current('finance.loan-products.*'),
                },
                {
                    href: route('finance.savings-products.index'),
                    label: t('nav.savingsProducts'),
                    active: route().current('finance.savings-products.*'),
                },
            );
        }
        cbsItems.push(...items);
    }

    if (cbsItems.length > 0) {
        const rankedCbsItems = [...cbsItems].sort(
            (a, b) => cbsRank(a.href) - cbsRank(b.href),
        );
        sections.push({
            id: 'cbs',
            title: 'CBS',
            items: rankedCbsItems,
        });
    }

    if (opsItems.length > 0) {
        sections.push({
            id: 'operations',
            title: 'Inventory & Parties',
            items: opsItems,
        });
    }

    if (user.can_edit_accounting && cf.crm) {
        const crmQ =
            user.role === 'admin' && currentCompanyId
                ? { company_id: currentCompanyId }
                : {};
        sections.push({
            id: 'crm',
            title: t('nav.crm'),
            items: [
                {
                    href: route('crm.dashboard', crmQ),
                    label: t('nav.crmOverview'),
                    active: route().current('crm.dashboard'),
                },
                {
                    href: route('crm.accounts.index', crmQ),
                    label: t('nav.crmAccounts'),
                    active: route().current('crm.accounts.*'),
                },
                {
                    href: route('crm.contacts.index', crmQ),
                    label: t('nav.crmContacts'),
                    active: route().current('crm.contacts.*'),
                },
                {
                    href: route('crm.opportunities.index', crmQ),
                    label: t('nav.crmOpportunities'),
                    active: route().current('crm.opportunities.*'),
                },
                {
                    href: route('crm.activities.index', crmQ),
                    label: t('nav.crmActivities'),
                    active: route().current('crm.activities.*'),
                },
            ],
        });
    }

    if (user.can_view_reports) {
        sections.push({
            id: 'reporting',
            title: t('nav.reporting'),
            items: [
                ...(user.can_create_journals
                    ? [
                          {
                              href: route('journals.create'),
                              label: t('nav.newJournal'),
                              active: route().current('journals.create'),
                          },
                          {
                              href: route('journals.create-cash-in'),
                              label: t('nav.cashIn'),
                              active: route().current(
                                  'journals.create-cash-in',
                              ),
                          },
                          {
                              href: route('journals.create-cash-out'),
                              label: t('nav.cashOut'),
                              active: route().current(
                                  'journals.create-cash-out',
                              ),
                          },
                      ]
                    : []),
                {
                    href: route('journals.index'),
                    label: t('nav.journals'),
                    active:
                        route().current('journals.*') &&
                        !route().current('journals.create') &&
                        !route().current('journals.create-cash-in') &&
                        !route().current('journals.create-cash-out'),
                },
                {
                    href: route('reports.index'),
                    label: t('nav.reports'),
                    active: route().current('reports.*'),
                },
                {
                    href: route('audit-trail.index'),
                    label: 'Audit trail',
                    active: route().current('audit-trail.*'),
                },
                {
                    href: route('bank-reconciliation.index'),
                    label: 'Bank reconciliation',
                    active: route().current('bank-reconciliation.*'),
                },
            ],
        });
    }

    return sections;
}

function SidebarNav({ sections, onNavigate }) {
    return (
        <nav className="flex flex-1 flex-col gap-6 overflow-y-auto p-3">
            {sections.map((section) => (
                <div key={section.id ?? section.title}>
                    <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {section.title}
                    </p>
                    <div className="space-y-0.5">
                        {section.items.map((item) => (
                            <SidebarNavLink
                                key={item.href}
                                href={item.href}
                                active={item.active}
                                onClick={onNavigate}
                            >
                                {item.label}
                            </SidebarNavLink>
                        ))}
                    </div>
                </div>
            ))}
        </nav>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const page = usePage();
    const user = page.props.auth.user ?? {};
    const flash = page.props.flash;
    const { t } = useTranslation();

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const pageUrl = page.url ?? '';

    const companyFeatures = page.props.company_features;
    const currentCompanyId = page.props.current_company_id;
    const approvalNotifications = page.props.approval_notifications ?? {
        total: 0,
        pending_members: 0,
        pending_savings_approvals: 0,
    };
    const approvalTotal = Number(approvalNotifications.total || 0);
    const pendingMembers = Number(approvalNotifications.pending_members || 0);
    const pendingSavings = Number(
        approvalNotifications.pending_savings_approvals || 0,
    );
    const approvalQuery =
        user.role === 'admin' && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};
    const verifyHref =
        pendingMembers > 0
            ? route('members.index', approvalQuery)
            : route('finance.positions.index', {
                  ...approvalQuery,
                  category: 'savings',
                  workspace: 'back',
              });

    const sections = useMemo(
        () =>
            buildNavSections(
                user,
                pageUrl,
                t,
                companyFeatures,
                currentCompanyId,
            ),
        [user, pageUrl, t, companyFeatures, currentCompanyId],
    );

    const closeMobile = () => setMobileMenuOpen(false);

    const isEndUser = user.role === 'end_user';

    const companyLine =
        user.role === 'admin'
            ? { label: t('layout.access'), name: t('layout.allCompanies') }
            : user.company?.name
              ? { label: t('layout.company'), name: user.company.name }
              : { label: t('layout.company'), name: '—' };

    return (
        <div
            className={
                isEndUser
                    ? 'min-h-screen bg-slate-100 dark:bg-slate-950'
                    : 'cbs-shell min-h-screen bg-slate-100/70 dark:bg-slate-950'
            }
        >
            {/* Mobile overlay */}
            <div
                className={`fixed inset-0 z-40 bg-slate-900/50 transition-opacity md:hidden ${
                    mobileMenuOpen
                        ? 'opacity-100'
                        : 'pointer-events-none opacity-0'
                }`}
                aria-hidden="true"
                onClick={closeMobile}
            />

            {/* Mobile slide-out sidebar */}
            <aside
                className={`cbs-sidebar fixed inset-y-0 left-0 z-50 flex w-64 max-w-[85vw] flex-col border-r shadow-xl transition-transform duration-200 ease-out md:hidden print:hidden ${
                    mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-14 shrink-0 items-center justify-between border-b border-slate-800 px-4">
                    <Link
                        href={route('dashboard')}
                        className="flex items-center gap-2"
                        onClick={closeMobile}
                    >
                        <ApplicationLogo className="h-8 w-8 shrink-0 text-white" />
                        <span className="text-sm font-semibold text-white">
                            {t('layout.workspace')}
                        </span>
                    </Link>
                    <button
                        type="button"
                        onClick={closeMobile}
                        className="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
                        aria-label={t('layout.closeMenu')}
                    >
                        <svg
                            className="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <SidebarNav sections={sections} onNavigate={closeMobile} />
            </aside>

            {/* Desktop sidebar */}
            <aside className="cbs-sidebar fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r md:flex print:hidden">
                <div className="flex h-14 shrink-0 items-center gap-2 border-b border-slate-800 px-4">
                    <Link
                        href={route('dashboard')}
                        className="flex items-center gap-2"
                    >
                        <ApplicationLogo className="h-8 w-8 shrink-0 text-white" />
                        <span className="text-sm font-semibold tracking-tight text-white">
                            {t('layout.workspace')}
                        </span>
                    </Link>
                </div>
                <SidebarNav sections={sections} />
            </aside>

            {/* Main column */}
            <div className="flex min-h-screen flex-col md:pl-64 print:pl-0">
                {/* Top bar (horizontal) */}
                <header className="cbs-topbar sticky top-0 z-20 border-b border-gray-200/70 shadow-sm print:hidden dark:border-border/70">
                    <div className="flex h-14 items-center gap-3 px-4 sm:px-6">
                        <button
                            type="button"
                            className="rounded-lg p-2 text-gray-600 hover:bg-gray-100 md:hidden dark:text-foreground dark:hover:bg-muted"
                            onClick={() => setMobileMenuOpen(true)}
                            aria-label={t('layout.openMenu')}
                        >
                            <svg
                                className="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </button>

                        <div className="min-w-0 flex-1 md:pl-0">
                            <p className="truncate text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-muted-foreground">
                                {companyLine.label}
                            </p>
                            <p className="truncate text-sm font-semibold text-gray-900 dark:text-card-foreground">
                                {companyLine.name}
                            </p>
                        </div>

                        <ThemeLanguageControls className="shrink-0 print:hidden" />

                        <div className="hidden h-8 w-px bg-gray-200 sm:block dark:bg-border" />

                        {approvalTotal > 0 && user?.can_edit_accounting ? (
                            <div className="hidden sm:flex sm:items-center sm:gap-2">
                                <Link
                                    href={verifyHref}
                                    className="inline-flex items-center rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white"
                                >
                                    Verify {approvalTotal}
                                </Link>
                                <div className="text-right text-xs text-gray-600 dark:text-muted-foreground">
                                    {pendingMembers > 0 ? (
                                        <div>
                                            <Link
                                                href={route(
                                                    'members.index',
                                                    approvalQuery,
                                                )}
                                                className="underline"
                                            >
                                                Members pending:{' '}
                                                {pendingMembers}
                                            </Link>
                                        </div>
                                    ) : null}
                                    {pendingSavings > 0 ? (
                                        <div>
                                            <Link
                                                href={route(
                                                    'finance.positions.index',
                                                    {
                                                        ...approvalQuery,
                                                        category: 'savings',
                                                        workspace: 'back',
                                                    },
                                                )}
                                                className="underline"
                                            >
                                                Savings approvals:{' '}
                                                {pendingSavings}
                                            </Link>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        ) : null}

                        <div className="hidden min-w-0 max-w-[12rem] text-right sm:block">
                            <p className="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-muted-foreground">
                                {t('layout.signedInAs')}
                            </p>
                            <p className="truncate text-sm font-medium text-gray-900 dark:text-card-foreground">
                                {user?.name}
                            </p>
                        </div>

                        <div className="relative shrink-0">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-muted"
                                    >
                                        <span className="hidden max-w-[8rem] truncate sm:inline">
                                            {user?.name}
                                        </span>
                                        <span className="sm:hidden">{t('layout.account')}</span>
                                        <svg
                                            className="h-4 w-4 text-gray-500"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content align="right" width="48">
                                    <div className="border-b border-gray-100 px-4 py-2 sm:hidden">
                                        <p className="text-xs text-gray-500">
                                            {user?.email}
                                        </p>
                                    </div>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        {t('layout.profile')}
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        {t('layout.logOut')}
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                {header && (
                    <div className="cbs-topbar border-b border-gray-200/70 dark:border-border/70">
                        <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </div>
                )}

                <main className="flex-1">
                    {flash?.error && (
                        <div className="print:hidden">
                            <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                                <div className="rounded-md bg-red-50 p-4 text-sm text-red-900 dark:bg-red-950/60 dark:text-red-100">
                                    {flash.error}
                                </div>
                            </div>
                        </div>
                    )}
                    {flash?.status && (
                        <div className="print:hidden">
                            <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                                <div className="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950 dark:text-green-100">
                                    {flash.status}
                                    {flash?.posted_journal_id ? (
                                        <div className="mt-2">
                                            <Link
                                                href={route('journals.show', {
                                                    journal: flash.posted_journal_id,
                                                    ...(user.role === 'admin' && currentCompanyId
                                                        ? {
                                                              company_id:
                                                                  currentCompanyId,
                                                          }
                                                        : {}),
                                                })}
                                                className="font-semibold underline"
                                            >
                                                View posted journal #
                                                {flash.posted_journal_id}
                                            </Link>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        </div>
                    )}
                    {children}
                    {isEndUser && companyFeatures?.members ? (
                        <PortalBottomNav />
                    ) : null}
                </main>
            </div>
        </div>
    );
}
