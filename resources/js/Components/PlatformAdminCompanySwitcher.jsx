import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function PlatformAdminCompanySwitcher({
    className = '',
    onNavigate,
}) {
    const { t } = useTranslation();
    const page = usePage();
    const companies = page.props.platform_admin_companies ?? [];
    const currentId = page.props.current_company_id;

    if (!Array.isArray(companies) || companies.length === 0) {
        return null;
    }

    return (
        <div className={className}>
            <label
                htmlFor="platform_admin_company_id"
                className="mb-1 block px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400"
            >
                {t('nav.organizationForSupport')}
            </label>
            <select
                id="platform_admin_company_id"
                className="mx-3 mb-2 block w-[calc(100%-1.5rem)] rounded-md border border-slate-600 bg-slate-900 px-2 py-2 text-sm text-white shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                value={currentId != null ? String(currentId) : ''}
                onChange={(e) => {
                    const id = e.target.value;
                    if (!id) {
                        return;
                    }
                    router.post(
                        route('platform.company-context.update'),
                        { company_id: id },
                        { preserveScroll: true },
                    );
                    onNavigate?.();
                }}
            >
                {currentId == null ? (
                    <option value="">{t('nav.pickOrganization')}</option>
                ) : null}
                {companies.map((c) => (
                    <option key={c.id} value={String(c.id)}>
                        {c.name}
                    </option>
                ))}
            </select>
        </div>
    );
}
