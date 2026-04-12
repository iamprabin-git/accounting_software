import Checkbox from '@/Components/Checkbox';
import CompanyWorkspaceSidebar from '@/Components/CompanyWorkspaceSidebar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Edit({ company, staffLoginUrl = '' }) {
    const page = usePage();
    const { t } = useTranslation();
    const canManageCompanySettings = Boolean(
        page.props.auth?.user?.can_manage_company_settings,
    );

    const { data, setData, post, processing, errors, progress, recentlySuccessful } =
        useForm({
            name: company.name,
            address: company.address || '',
            phone: company.phone || '',
            contact_email: company.contact_email || '',
            bank_payment_details: company.bank_payment_details || '',
            payment_qr: null,
            remove_payment_qr: false,
            portal_show_payment_details: Boolean(
                company.portal_show_payment_details,
            ),
            logo: null,
            remove_logo: false,
        });

    const submit = (e) => {
        e.preventDefault();
        if (!canManageCompanySettings) {
            return;
        }
        post(route('company.profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setData('logo', null);
                setData('remove_logo', false);
                setData('payment_qr', null);
                setData('remove_payment_qr', false);
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {t('nav.companyProfile')}
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            {t('companyWorkspace.profilePageSubtitle')}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={route('profile.edit')}
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Your account
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={t('nav.companyProfile')} />

            <div className="py-8 sm:py-12">
                <div className="mx-auto flex w-full min-w-0 max-w-6xl flex-col gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:gap-10 lg:px-8">
                    <CompanyWorkspaceSidebar />
                    <div className="min-w-0 flex-1">
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-6 lg:p-8">
                        <section>
                            <header>
                                <h3 className="text-lg font-medium text-gray-900">
                                    Organization details
                                </h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    This information appears on printed journals
                                    and financial reports (letterhead). Upload
                                    your logo and keep address and contact
                                    details current.
                                </p>
                            </header>

                            {staffLoginUrl ? (
                                <div className="mt-4 rounded-lg border border-indigo-100 bg-indigo-50/80 p-4 dark:border-border dark:bg-muted/40">
                                    <h4 className="text-sm font-semibold text-gray-900 dark:text-foreground">
                                        Staff &amp; member sign-in page
                                    </h4>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-muted-foreground">
                                        Share this link with your team and portal
                                        users. They will see your organization
                                        name and logo on the sign-in screen.
                                    </p>
                                    <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <code className="block max-w-full overflow-x-auto rounded bg-white px-2 py-1.5 text-xs text-gray-800 ring-1 ring-gray-200 dark:bg-background dark:text-foreground dark:ring-border">
                                            {staffLoginUrl}
                                        </code>
                                        <button
                                            type="button"
                                            className="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                            onClick={() => {
                                                void navigator.clipboard.writeText(
                                                    staffLoginUrl,
                                                );
                                            }}
                                        >
                                            Copy link
                                        </button>
                                    </div>
                                </div>
                            ) : null}

                            {!canManageCompanySettings ? (
                                <div className="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                                    {t('companyWorkspace.readOnlySettingsHint')}
                                </div>
                            ) : null}

                            <form
                                onSubmit={submit}
                                className="mt-6 space-y-6"
                                encType="multipart/form-data"
                            >
                                <fieldset
                                    disabled={!canManageCompanySettings}
                                    className={
                                        'min-w-0 space-y-6 border-0 p-0 ' +
                                        (!canManageCompanySettings
                                            ? 'opacity-[0.72]'
                                            : '')
                                    }
                                >
                                <div>
                                    <InputLabel htmlFor="company_name" value="Company name" />
                                    <TextInput
                                        id="company_name"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div>
                                    <InputLabel htmlFor="address" value="Address" />
                                    <textarea
                                        id="address"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={4}
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.address}
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="phone"
                                        value="Contact number"
                                    />
                                    <TextInput
                                        id="phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.phone}
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="contact_email"
                                        value="Public contact email (shown to members)"
                                    />
                                    <TextInput
                                        id="contact_email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        value={data.contact_email}
                                        onChange={(e) =>
                                            setData(
                                                'contact_email',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. support@yourcoop.org"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Used on the member portal &quot;Contact
                                        us&quot; section. Leave empty to hide.
                                    </p>
                                    <InputError
                                        className="mt-2"
                                        message={errors.contact_email}
                                    />
                                </div>

                                <div className="border-t border-gray-200 pt-8 mt-8">
                                    <header>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Bank details &amp; payment QR (member
                                            portal)
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Shown to members when{' '}
                                            <span className="font-medium">
                                                Show bank details and payment QR…
                                            </span>{' '}
                                            is enabled below. It appears on their
                                            dashboard, Accounts overview, Passbook,
                                            loan/savings account pages, and
                                            Messages. Add your bank name, branch,
                                            account title and number, and upload a
                                            QR image (e.g. from your bank or wallet
                                            app) for scan-to-pay.
                                        </p>
                                    </header>

                                    <div className="mt-6 space-y-6">
                                        <div>
                                            <InputLabel
                                                htmlFor="bank_payment_details"
                                                value="Bank / payment instructions"
                                            />
                                            <textarea
                                                id="bank_payment_details"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                rows={6}
                                                value={data.bank_payment_details}
                                                onChange={(e) =>
                                                    setData(
                                                        'bank_payment_details',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="e.g. Bank name, branch, account title, account number, SWIFT…"
                                            />
                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors.bank_payment_details
                                                }
                                            />
                                        </div>

                                        <div>
                                            <InputLabel value="Payment QR image" />
                                            {company.payment_qr_url &&
                                                !data.remove_payment_qr && (
                                                    <div className="mt-2">
                                                        <img
                                                            src={
                                                                company.payment_qr_url
                                                            }
                                                            alt="Current payment QR"
                                                            className="h-44 w-44 rounded border border-gray-200 object-contain bg-white p-2"
                                                        />
                                                    </div>
                                                )}
                                            <input
                                                id="payment_qr"
                                                type="file"
                                                accept="image/*"
                                                className="mt-2 block w-full text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
                                                onChange={(e) =>
                                                    setData(
                                                        'payment_qr',
                                                        e.target.files?.[0] ||
                                                            null,
                                                    )
                                                }
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                PNG, JPG, or WebP. Max 2&nbsp;MB.
                                            </p>
                                            {company.payment_qr_url && (
                                                <div className="mt-3 flex items-center gap-2">
                                                    <Checkbox
                                                        id="remove_payment_qr"
                                                        name="remove_payment_qr"
                                                        checked={
                                                            data.remove_payment_qr
                                                        }
                                                        onChange={(e) =>
                                                            setData(
                                                                'remove_payment_qr',
                                                                e.target.checked,
                                                            )
                                                        }
                                                    />
                                                    <InputLabel
                                                        htmlFor="remove_payment_qr"
                                                        value="Remove current payment QR"
                                                    />
                                                </div>
                                            )}
                                            <InputError
                                                className="mt-2"
                                                message={errors.payment_qr}
                                            />
                                        </div>

                                        <div className="flex items-start gap-2">
                                            <Checkbox
                                                id="portal_show_payment_details"
                                                name="portal_show_payment_details"
                                                checked={
                                                    data.portal_show_payment_details
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        'portal_show_payment_details',
                                                        e.target.checked,
                                                    )
                                                }
                                            />
                                            <div>
                                                <InputLabel
                                                    htmlFor="portal_show_payment_details"
                                                    value="Show bank details and payment QR to end users in the portal"
                                                />
                                                <p className="text-xs text-gray-500 mt-1">
                                                    When off, members do not see
                                                    bank text or the QR anywhere
                                                    in the portal (even if saved
                                                    here).
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                                    <p>
                                        <span className="font-medium">
                                            Journal period lock &amp; close/reopen
                                        </span>{' '}
                                        — use{' '}
                                        <Link
                                            href={route(
                                                'company.configuration.edit',
                                            )}
                                            className="font-medium text-indigo-600 underline hover:text-indigo-800"
                                        >
                                            CBS, backup &amp; period close
                                        </Link>
                                        .
                                    </p>
                                </div>

                                <div>
                                    <InputLabel value="Logo" />
                                    {company.logo_url && !data.remove_logo && (
                                        <div className="mt-2 flex items-center gap-4">
                                            <img
                                                src={company.logo_url}
                                                alt=""
                                                className="h-20 max-w-[200px] rounded border border-gray-200 object-contain p-1"
                                            />
                                        </div>
                                    )}
                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        className="mt-2 block w-full text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700"
                                        onChange={(e) =>
                                            setData(
                                                'logo',
                                                e.target.files?.[0] || null,
                                            )
                                        }
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        PNG, JPG, or WebP. Max 2&nbsp;MB.
                                    </p>
                                    {company.logo_url && (
                                        <div className="mt-3 flex items-center gap-2">
                                            <Checkbox
                                                id="remove_logo"
                                                name="remove_logo"
                                                checked={data.remove_logo}
                                                onChange={(e) =>
                                                    setData(
                                                        'remove_logo',
                                                        e.target.checked,
                                                    )
                                                }
                                            />
                                            <InputLabel
                                                htmlFor="remove_logo"
                                                value="Remove current logo"
                                            />
                                        </div>
                                    )}
                                    <InputError
                                        className="mt-2"
                                        message={errors.logo}
                                    />
                                </div>

                                {progress && (
                                    <progress
                                        className="h-2 w-full"
                                        value={progress.percentage}
                                        max="100"
                                    >
                                        {progress.percentage}%
                                    </progress>
                                )}

                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>
                                        Save company profile
                                    </PrimaryButton>
                                    {recentlySuccessful && (
                                        <span className="text-sm text-gray-600">
                                            Saved.
                                        </span>
                                    )}
                                </div>
                                </fieldset>
                            </form>
                        </section>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
