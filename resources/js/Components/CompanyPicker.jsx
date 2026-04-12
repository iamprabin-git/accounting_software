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
        <label className="flex w-full min-w-0 flex-col gap-2 text-sm text-gray-700 sm:w-auto sm:flex-row sm:items-center">
            <span className="shrink-0 font-medium sm:whitespace-nowrap">
                Company
            </span>
            <select
                className="w-full min-w-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
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
