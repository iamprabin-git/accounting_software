import { cn } from '@/lib/utils';
import { Briefcase, UserCircle } from 'lucide-react';

const ROLE_META = {
    staff: {
        title: 'Staff',
        description:
            'Can draft journals, manage operational data, and work in the ledger. Actions may require approval from an authorized reviewer.',
        icon: Briefcase,
        accent:
            'border-indigo-200 bg-indigo-50/80 ring-indigo-500/20 dark:border-indigo-900/60 dark:bg-indigo-950/40',
        selectedRing: 'ring-2 ring-indigo-500 dark:ring-indigo-400',
    },
    end_user: {
        title: 'End user',
        description:
            'Signs in to the member portal to view loans, savings, passbook, and messages. No access to the accounting workspace.',
        icon: UserCircle,
        accent:
            'border-emerald-200 bg-emerald-50/80 ring-emerald-500/20 dark:border-emerald-900/60 dark:bg-emerald-950/40',
        selectedRing: 'ring-2 ring-emerald-600 dark:ring-emerald-500',
    },
};

export default function RoleCardPicker({
    roles = [],
    value,
    onChange,
    disabled = false,
    name = 'role',
}) {
    return (
        <fieldset disabled={disabled} className="space-y-3">
            <legend className="sr-only">Account role</legend>
            <div className="grid gap-3 sm:grid-cols-2">
                {roles.map((r) => {
                    const meta = ROLE_META[r] ?? {
                        title: r,
                        description: '',
                        icon: UserCircle,
                        accent: 'border-border bg-muted/30',
                        selectedRing: 'ring-2 ring-primary',
                    };
                    const Icon = meta.icon;
                    const selected = value === r;
                    return (
                        <label
                            key={r}
                            className={cn(
                                'relative flex cursor-pointer flex-col gap-2 rounded-xl border p-4 text-left shadow-sm transition-all',
                                meta.accent,
                                selected ? meta.selectedRing : 'hover:border-foreground/20',
                                disabled && 'cursor-not-allowed opacity-60',
                            )}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={r}
                                checked={selected}
                                onChange={() => onChange(r)}
                                className="sr-only"
                            />
                            <div className="flex items-start gap-3">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-background/80 text-foreground shadow-sm">
                                    <Icon className="h-5 w-5" aria-hidden />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-foreground">
                                        {meta.title}
                                    </p>
                                    {meta.description ? (
                                        <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                                            {meta.description}
                                        </p>
                                    ) : null}
                                </div>
                            </div>
                        </label>
                    );
                })}
            </div>
        </fieldset>
    );
}
