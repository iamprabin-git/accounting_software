import { router } from '@inertiajs/react';

export default function CompanyPicker({
    companies,
    currentCompanyId,
    routeName,
    routeParams = {},
    query = {},
}) {
    if (!companies?.length) {
        return null;
    }

    return (
        <label className="flex items-center gap-2 text-sm text-gray-700">
            <span className="whitespace-nowrap font-medium">Company</span>
            <select
                className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                value={String(currentCompanyId)}
                onChange={(e) =>
                    router.get(
                        route(routeName, routeParams),
                        {
                            ...query,
                            company_id: Number(e.target.value),
                        },
                        { preserveState: true, replace: true },
                    )
                }
            >
                {companies.map((c) => (
                    <option key={c.id} value={c.id}>
                        {c.name}
                    </option>
                ))}
            </select>
        </label>
    );
}
