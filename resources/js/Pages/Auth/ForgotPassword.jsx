import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function ForgotPassword({
    status,
    loginBranding = null,
    loginCompanyId = null,
}) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        company_id: loginCompanyId ?? '',
    });

    useEffect(() => {
        if (loginCompanyId != null && loginCompanyId !== '') {
            setData('company_id', loginCompanyId);
        }
    }, [loginCompanyId, setData]);

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    const loginHref =
        loginCompanyId != null && loginCompanyId !== ''
            ? `${route('login')}?company_id=${loginCompanyId}`
            : route('login');

    return (
        <GuestLayout branding={loginBranding}>
            <Head title="Forgot Password" />

            <div className="mb-4 text-sm text-gray-600">
                Forgot your password? No problem. Just let us know your email
                address and we will email you a password reset link that will
                allow you to choose a new one.
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </div>

                <InputError message={errors.email} className="mt-2" />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Link
                        href={loginHref}
                        className="text-sm text-gray-600 underline hover:text-gray-900 dark:text-muted-foreground dark:hover:text-foreground"
                    >
                        Back to sign in
                    </Link>
                    <PrimaryButton disabled={processing}>
                        Email Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
