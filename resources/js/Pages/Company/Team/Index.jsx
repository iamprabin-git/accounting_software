import { HorizontalBarGroup } from '@/Components/dashboard/SimpleBars';
import RoleBadge from '@/Components/Team/RoleBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Head, Link, router } from '@inertiajs/react';
import { UserPlus, Users } from 'lucide-react';
import { useMemo } from 'react';

function StatusPill({ children, variant = 'neutral' }) {
    const styles = {
        neutral: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
        success:
            'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-100',
        warning:
            'bg-amber-100 text-amber-950 dark:bg-amber-950/50 dark:text-amber-100',
        muted: 'bg-muted text-muted-foreground',
    };
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${styles[variant]}`}
        >
            {children}
        </span>
    );
}

function UserTable({
    title,
    description,
    members,
    emptyMessage,
    memberKind = 'end_user',
}) {
    return (
        <Card className="cbs-surface overflow-hidden border-slate-200/90 shadow-sm dark:border-slate-800">
            <CardHeader className="border-b border-border/60 bg-muted/30 pb-4">
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="text-lg">{title}</CardTitle>
                        {description ? (
                            <CardDescription className="mt-1 max-w-3xl">
                                {description}
                            </CardDescription>
                        ) : null}
                    </div>
                    <RoleBadge kind={memberKind} className="shrink-0 sm:mt-0" />
                </div>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="min-w-[44rem] w-full divide-y divide-border text-sm sm:min-w-full">
                        <thead className="bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Email
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Portal
                                </th>
                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border bg-card">
                            {members.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-12 text-center text-muted-foreground"
                                    >
                                        {emptyMessage}
                                    </td>
                                </tr>
                            ) : (
                                members.map((m) => (
                                    <tr
                                        key={m.id}
                                        className="transition-colors hover:bg-muted/25"
                                    >
                                        <td className="max-w-[12rem] truncate px-4 py-3 font-medium text-foreground sm:max-w-none">
                                            {m.name}
                                        </td>
                                        <td className="max-w-[14rem] truncate px-4 py-3 text-muted-foreground sm:max-w-none">
                                            {m.email}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3">
                                            {memberKind === 'staff' &&
                                            !m.is_active ? (
                                                <div className="flex flex-col gap-2">
                                                    <StatusPill variant="warning">
                                                        Pending activation
                                                    </StatusPill>
                                                    <button
                                                        type="button"
                                                        className="text-left text-xs font-medium text-primary underline-offset-4 hover:underline"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    'company.team.activate',
                                                                    m.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Activate account
                                                    </button>
                                                </div>
                                            ) : m.is_active ? (
                                                <StatusPill variant="success">
                                                    Active
                                                </StatusPill>
                                            ) : (
                                                <StatusPill variant="muted">
                                                    Inactive
                                                </StatusPill>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {m.role === 'end_user' ? (
                                                <div className="flex flex-col gap-2">
                                                    {m.portal_approved_at ? (
                                                        <StatusPill variant="success">
                                                            Approved
                                                        </StatusPill>
                                                    ) : (
                                                        <StatusPill variant="warning">
                                                            Pending approval
                                                        </StatusPill>
                                                    )}
                                                    <div className="flex flex-wrap gap-2">
                                                        {!m.portal_approved_at ? (
                                                            <button
                                                                type="button"
                                                                className="text-xs font-medium text-primary underline-offset-4 hover:underline"
                                                                onClick={() =>
                                                                    router.post(
                                                                        route(
                                                                            'company.team.approve-portal',
                                                                            m.id,
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                Approve portal
                                                            </button>
                                                        ) : null}
                                                        {m.portal_approved_at ? (
                                                            <button
                                                                type="button"
                                                                className="text-xs font-medium text-destructive underline-offset-4 hover:underline"
                                                                onClick={() => {
                                                                    if (
                                                                        confirm(
                                                                            `Revoke portal access for ${m.name}?`,
                                                                        )
                                                                    ) {
                                                                        router.post(
                                                                            route(
                                                                                'company.team.revoke-portal',
                                                                                m.id,
                                                                            ),
                                                                        );
                                                                    }
                                                                }}
                                                            >
                                                                Revoke
                                                            </button>
                                                        ) : null}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground/70">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right">
                                            <div className="flex flex-col items-end gap-2 sm:flex-row sm:justify-end sm:gap-3">
                                                <Link
                                                    href={route(
                                                        'company.team.edit',
                                                        m.id,
                                                    )}
                                                    className="text-xs font-medium text-primary underline-offset-4 hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    className="text-xs font-medium text-destructive underline-offset-4 hover:underline"
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                `Remove ${m.name} from your organization?`,
                                                            )
                                                        ) {
                                                            router.delete(
                                                                route(
                                                                    'company.team.destroy',
                                                                    m.id,
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({ staffMembers, endUserMembers }) {
    const stats = useMemo(() => {
        const staff = staffMembers ?? [];
        const endUsers = endUserMembers ?? [];
        const pendingStaff = staff.filter((m) => !m.is_active).length;
        const pendingPortal = endUsers.filter(
            (m) => !m.portal_approved_at,
        ).length;
        return {
            staffCount: staff.length,
            endUserCount: endUsers.length,
            pendingStaff,
            pendingPortal,
            total: staff.length + endUsers.length,
        };
    }, [staffMembers, endUserMembers]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight tracking-tight text-foreground">
                            Team &amp; portal users
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Staff access the workspace; end users use the member
                            portal.
                        </p>
                    </div>
                    <Button asChild className="w-full shrink-0 sm:w-auto">
                        <Link
                            href={route('company.team.create')}
                            className="inline-flex items-center gap-2"
                        >
                            <UserPlus className="h-4 w-4" />
                            Add user
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Users" />

            <div className="space-y-8 px-4 py-8 sm:space-y-10 sm:px-6 sm:py-10 lg:px-8">
                <div className="mx-auto min-w-0 max-w-7xl space-y-6">
                    <Card className="cbs-surface border-slate-200/90 dark:border-slate-800">
                        <CardHeader className="flex flex-row items-center gap-2 space-y-0 pb-2">
                            <Users className="h-5 w-5 text-muted-foreground" />
                            <CardTitle className="text-base">
                                Directory overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6 lg:grid-cols-2">
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                                <div className="rounded-lg border bg-muted/20 p-3">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Total users
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                                        {stats.total}
                                    </p>
                                </div>
                                <div className="rounded-lg border bg-muted/20 p-3">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Staff
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                                        {stats.staffCount}
                                    </p>
                                </div>
                                <div className="rounded-lg border bg-muted/20 p-3">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        End users
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold tabular-nums">
                                        {stats.endUserCount}
                                    </p>
                                </div>
                                <div className="rounded-lg border bg-muted/20 p-3">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Awaiting action
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold tabular-nums text-amber-700 dark:text-amber-400">
                                        {stats.pendingStaff +
                                            stats.pendingPortal}
                                    </p>
                                </div>
                            </div>
                            <div className="rounded-lg border bg-muted/15 p-4">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Headcount mix
                                </p>
                                <HorizontalBarGroup
                                    className="mt-3"
                                    items={[
                                        {
                                            key: 's',
                                            label: 'Staff',
                                            value: stats.staffCount,
                                        },
                                        {
                                            key: 'e',
                                            label: 'End users',
                                            value: stats.endUserCount,
                                        },
                                    ]}
                                />
                                <HorizontalBarGroup
                                    className="mt-4 border-t border-border/60 pt-4"
                                    title="Attention needed"
                                    items={[
                                        {
                                            key: 'ps',
                                            label: 'Staff pending activation',
                                            value: stats.pendingStaff,
                                            tone:
                                                stats.pendingStaff > 0
                                                    ? 'warning'
                                                    : undefined,
                                        },
                                        {
                                            key: 'pp',
                                            label: 'Portal pending approval',
                                            value: stats.pendingPortal,
                                            tone:
                                                stats.pendingPortal > 0
                                                    ? 'warning'
                                                    : undefined,
                                        },
                                    ]}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <UserTable
                        title="Staff"
                        description="New staff accounts stay inactive until you activate them. They can then sign in and draft journals and other work for your approval."
                        members={staffMembers}
                        emptyMessage="No staff users yet. Add a user and choose the Staff role."
                        memberKind="staff"
                    />

                    <UserTable
                        title="End users"
                        description="End users sign in to the customer app. Approve portal access after their member record is set up."
                        members={endUserMembers}
                        emptyMessage="No end users yet. Add a user and choose the End user role."
                        memberKind="end_user"
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
