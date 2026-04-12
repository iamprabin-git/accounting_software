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
import { ArrowLeft, UserPlus } from 'lucide-react';

export default function Create({ roles }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: roles[0] ?? 'staff',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('company.team.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight tracking-tight text-foreground">
                        Add user
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Create a staff or end-user login for your organization.
                    </p>
                </div>
            }
        >
            <Head title="Add user" />

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
                            <div className="flex items-center gap-2">
                                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <UserPlus className="h-5 w-5" />
                                </span>
                                <div>
                                    <CardTitle>New user</CardTitle>
                                    <CardDescription className="mt-1">
                                        Staff accounts may require activation
                                        before first sign-in.
                                    </CardDescription>
                                </div>
                            </div>
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
                                        autoComplete="email"
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

                                <div className="space-y-2">
                                    <InputLabel
                                        htmlFor="password"
                                        value="Password"
                                    />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        required
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
                                        value="Confirm password"
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
                                        required
                                        autoComplete="new-password"
                                    />
                                </div>

                                <div className="flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:items-center">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full sm:w-auto"
                                    >
                                        Create user
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
