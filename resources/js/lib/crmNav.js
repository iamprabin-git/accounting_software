/** Query params so platform admins stay on the selected organization in CRM. */
export function crmCompanyQuery(user, currentCompanyId) {
    if (user?.role === 'admin' && currentCompanyId) {
        return { company_id: currentCompanyId };
    }
    return {};
}
