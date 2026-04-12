import { Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

function NavItem({ href, active, children }) {
    return (
        <Link
            href={href}
            className={
                'block touch-manipulation rounded-lg px-3 py-2.5 text-sm font-medium transition ' +
                (active
                    ? 'bg-indigo-50 text-indigo-900 ring-1 ring-inset ring-indigo-200'
                    : 'text-gray-700 hover:bg-gray-100')
            }
        >
            {children}
        </Link>
    );
}

function NavGroup({ title, children }) {
    return (
        <div className="mb-4 last:mb-0">
            {title ? (
                <p className="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">
                    {title}
                </p>
            ) : null}
            <nav className="flex w-full min-w-0 flex-col gap-0.5">{children}</nav>
        </div>
    );
}

export default function CompanyWorkspaceSidebar({ className = '' }) {
    const page = usePage();
    const user = page.props.auth?.user ?? {};
    const currentCompanyId = page.props.current_company_id;
    const companyFeatures = page.props.company_features ?? {};
    const { t } = useTranslation();

    const q =
        user.role === 'admin' && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    const groups = useMemo(() => {
        const organizationItems = [
            {
                href: route('company.profile.edit', q),
                label: t('nav.companyProfile'),
                active: route().current('company.profile.*'),
            },
        ];

        if (user.role === 'company' && user.can_manage_team) {
            organizationItems.push({
                href: route('company.team.index'),
                label: t('nav.team'),
                active: route().current('company.team.*'),
            });
        }

        if (
            companyFeatures.members &&
            (user.role === 'company' || user.role === 'staff')
        ) {
            organizationItems.push({
                href: route('company.customer-chat.index', q),
                label: t('nav.customerMessages'),
                active: route().current('company.customer-chat.*'),
            });
        }

        const accountingItems = [
            {
                href: route('company.configuration.edit', q),
                label: t('nav.companyConfiguration'),
                active: route().current('company.configuration.*'),
            },
            {
                href: route('company.holidays.index', q),
                label: t('nav.companyHolidays'),
                active: route().current('company.holidays.*'),
            },
        ];

        const integrationItems = [];
        if (user.role !== 'staff') {
            integrationItems.push({
                href: route('company.integrations.index', q),
                label: t('nav.companyIntegrations'),
                active: route().current('company.integrations.*'),
            });
        }

        const out = [
            {
                id: 'organization',
                title: null,
                items: organizationItems,
            },
            {
                id: 'accounting',
                title: t('companyWorkspace.navGroupAccounting'),
                items: accountingItems,
            },
        ];
        if (integrationItems.length > 0) {
            out.push({
                id: 'integrations',
                title: t('companyWorkspace.navGroupIntegrations'),
                items: integrationItems,
            });
        }

        return out;
    }, [companyFeatures.members, currentCompanyId, t, user.can_manage_team, user.role]);

    return (
        <aside
            className={`w-full min-w-0 max-w-full shrink-0 lg:w-56 ${className}`.trim()}
            aria-label={t('nav.company')}
        >
            <div className="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                {groups.map((group) => (
                    <NavGroup key={group.id} title={group.title}>
                        {group.items.map((item) => (
                            <NavItem
                                key={item.href}
                                href={item.href}
                                active={item.active}
                            >
                                {item.label}
                            </NavItem>
                        ))}
                    </NavGroup>
                ))}
            </div>
        </aside>
    );
}
