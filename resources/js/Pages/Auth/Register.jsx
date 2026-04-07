import GoogleSignInButton from '@/Components/GoogleSignInButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Register({ googleAuthEnabled }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={t('auth.register')} />
            <div className="mb-5">
                <h1 className="text-xl font-semibold tracking-tight text-foreground">
                    {t('auth.register')}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Create your organization account and get started.
                </p>
            </div>

            {googleAuthEnabled && (
                <div className="mb-6 space-y-4">
                    <GoogleSignInButton />
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t border-gray-200 dark:border-border" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-white px-2 text-gray-500 dark:bg-card dark:text-muted-foreground">
                                {t('auth.orRegisterEmail')}
                            </span>
                        </div>
                    </div>
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="name" value={t('auth.name')} />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value={t('auth.email')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value={t('auth.password')} />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value={t('auth.confirmPassword')}
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-muted-foreground dark:hover:text-foreground"
                    >
                        {t('auth.alreadyRegistered')}
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        {t('auth.register')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
