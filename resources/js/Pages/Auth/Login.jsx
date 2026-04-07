import Checkbox from '@/Components/Checkbox';
import GoogleSignInButton from '@/Components/GoogleSignInButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

export default function Login({
    status,
    canResetPassword,
    googleAuthEnabled,
    loginBranding = null,
    loginCompanyId = null,
}) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        company_id: loginCompanyId ?? '',
    });

    useEffect(() => {
        if (loginCompanyId != null && loginCompanyId !== '') {
            setData('company_id', loginCompanyId);
        }
    }, [loginCompanyId, setData]);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const forgotHref =
        loginCompanyId != null && loginCompanyId !== ''
            ? `${route('password.request')}?company_id=${loginCompanyId}`
            : route('password.request');

    return (
        <GuestLayout branding={loginBranding}>
            <Head title={t('auth.login')} />
            <div className="mb-5">
                <h1 className="text-xl font-semibold tracking-tight text-foreground">
                    {t('auth.login')}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Access your CBS workspace securely.
                </p>
            </div>

            {status && (
                <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-300">
                    {status}
                </div>
            )}

            {googleAuthEnabled && (
                <div className="mb-6 space-y-4">
                    <GoogleSignInButton />
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t border-gray-200 dark:border-border" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-white px-2 text-gray-500 dark:bg-card dark:text-muted-foreground">
                                {t('auth.orEmail')}
                            </span>
                        </div>
                    </div>
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value={t('auth.email')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
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
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600 dark:text-muted-foreground">
                            {t('auth.remember')}
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    {canResetPassword && (
                        <Link
                            href={forgotHref}
                            className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-muted-foreground dark:hover:text-foreground"
                        >
                            {t('auth.forgot')}
                        </Link>
                    )}

                    <PrimaryButton className="ms-4" disabled={processing}>
                        {t('auth.login')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
