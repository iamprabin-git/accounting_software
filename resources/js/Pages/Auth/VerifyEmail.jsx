import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />
            <div className="mb-5">
                <h1 className="text-xl font-semibold tracking-tight text-foreground">
                    Email Verification
                </h1>
            </div>

            <div className="mb-4 text-sm text-gray-600 dark:text-muted-foreground">
                Thanks for signing up! Before getting started, could you verify
                your email address by clicking on the link we just emailed to
                you? If you didn't receive the email, we will gladly send you
                another.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 dark:border-green-900/40 dark:bg-green-950/40 dark:text-green-300">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        Resend Verification Email
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-muted-foreground dark:hover:text-foreground"
                    >
                        Log Out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
