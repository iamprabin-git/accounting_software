import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import RoleCardPicker from '@/Components/Team/RoleCardPicker';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function Edit({ member, roles }) {
    const { data, setData, put, processing, errors } = useForm({
        name: member.name,
        email: member.email,
        password: '',
        password_confirmation: '',
        role: member.role,
        is_active: Boolean(member.is_active),
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('company.team.update', member.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight tracking-tight text-foreground">
                        Edit user
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Update profile, role, and sign-in access.
                    </p>
                </div>
            }
        >
            <Head title="Edit user" />

            <div className="px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div className="mx-auto max-w-2xl">
                    <Button variant="ghost" size="sm" className="mb-6 gap-1" asChild>
                        <Link href={route('company.team.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to team
                        </Link>
                    </Button>

                    <Card className="cbs-surface border-slate-200/90 shadow-sm dark:border-slate-800">
                        <CardHeader>
                            <CardTitle>Account details</CardTitle>
                            <CardDescription>
                                Changes apply on save. Leave password fields
                                empty to keep the current password.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-8">
                                <div className="space-y-2">
                                    <InputLabel htmlFor="name" value="Name" />
                                    <TextInput
                                        id="name"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <InputLabel htmlFor="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.email}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-3">
                                    <InputLabel value="Role" />
                                    <RoleCardPicker
                                        roles={roles}
                                        value={data.role}
                                        onChange={(r) => setData('role', r)}
                                        disabled={processing}
                                    />
                                    <InputError
                                        message={errors.role}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="rounded-lg border border-border bg-muted/20 p-4">
                                    <div className="flex items-start gap-3">
                                        <Checkbox
                                            id="is_active"
                                            name="is_active"
                                            checked={data.is_active}
                                            onChange={(e) =>
                                                setData(
                                                    'is_active',
                                                    e.target.checked,
                                                )
                                            }
                                        />
                                        <div>
                                            <InputLabel
                                                htmlFor="is_active"
                                                value="Account active"
                                            />
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Inactive users cannot sign in.
                                                Subscription limits still apply.
                                            </p>
                                        </div>
                                    </div>
                                    <InputError
                                        message={errors.is_active}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <InputLabel
                                        htmlFor="password"
                                        value="New password (optional)"
                                    />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        autoComplete="new-password"
                                    />
                                    <InputError
                                        message={errors.password}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <InputLabel
                                        htmlFor="password_confirmation"
                                        value="Confirm new password"
                                    />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={data.password_confirmation}
                                        onChange={(e) =>
                                            setData(
                                                'password_confirmation',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="new-password"
                                    />
                                </div>

                                <div className="flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:items-center">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full sm:w-auto"
                                    >
                                        Save changes
                                    </Button>
                                    <Button
                                        variant="outline"
                                        type="button"
                                        className="w-full sm:w-auto"
                                        asChild
                                    >
                                        <Link href={route('company.team.index')}>
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
