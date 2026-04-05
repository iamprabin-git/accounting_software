import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

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
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Edit user
                </h2>
            }
        >
            <Head title="Edit user" />

            <div className="py-10">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg"
                    >
                        <div>
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

                        <div>
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

                        <div>
                            <InputLabel htmlFor="role" value="Role" />
                            <select
                                id="role"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.role}
                                onChange={(e) =>
                                    setData('role', e.target.value)
                                }
                            >
                                {roles.map((r) => (
                                    <option key={r} value={r}>
                                        {r === 'staff'
                                            ? 'Staff (can edit books)'
                                            : 'End user (view only)'}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.role}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    checked={data.is_active}
                                    onChange={(e) =>
                                        setData('is_active', e.target.checked)
                                    }
                                />
                                <InputLabel
                                    htmlFor="is_active"
                                    value="Account active (can sign in if subscription allows)"
                                />
                            </div>
                            <InputError
                                message={errors.is_active}
                                className="mt-2"
                            />
                        </div>

                        <div>
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
                            />
                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div>
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
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Save changes
                            </PrimaryButton>
                            <Link
                                href={route('company.team.index')}
                                className="text-sm text-gray-600 underline"
                            >
                                Back
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
