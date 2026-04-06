import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({
    company,
    inventoryAccountOptions = [],
    staffLoginUrl = '',
}) {
    const { data, setData, post, processing, errors, progress, recentlySuccessful } =
        useForm({
            name: company.name,
            address: company.address || '',
            phone: company.phone || '',
            bank_payment_details: company.bank_payment_details || '',
            payment_qr: null,
            remove_payment_qr: false,
            portal_show_payment_details: Boolean(
                company.portal_show_payment_details,
            ),
            logo: null,
            remove_logo: false,
            inventory_chart_account_id:
                company.inventory_chart_account_id != null
                    ? String(company.inventory_chart_account_id)
                    : '',
            journal_lock_date: company.journal_lock_date || '',
            dual_approval_threshold:
                company.dual_approval_threshold != null
                    ? String(company.dual_approval_threshold)
                    : '',
        });
    const closePeriodForm = useForm({
        close_lock_date: company.journal_lock_date || '',
        close_reason: '',
    });
    const reopenPeriodForm = useForm({
        reopen_to_date: '',
        reopen_reason: '',
    });

    const submit = (e) => {
        e.preventDefault();
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
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Company profile
                    </h2>
                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={route('company.integrations.index')}
                            className="text-sm text-indigo-600 underline hover:text-indigo-800"
                        >
                            Integrations &amp; API
                        </Link>
                        <Link
                            href={route('profile.edit')}
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Back to your account profile
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Company profile" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
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

                            <form
                                onSubmit={submit}
                                className="mt-6 space-y-6"
                                encType="multipart/form-data"
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

                                <div className="border-t border-gray-200 pt-8 mt-8">
                                    <header>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Bank details & payment QR
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            End users see this in the customer
                                            portal (home, passbook, and account
                                            pages) when you enable display below.
                                            Add your bank name, branch, account
                                            name and number, and optionally
                                            upload a QR image (e.g. from your
                                            bank or wallet app).
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
                                                    When off, customers do not
                                                    see this block even if text
                                                    or an image is saved.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {inventoryAccountOptions.length > 0 && (
                                    <div>
                                        <InputLabel
                                            htmlFor="inventory_chart_account_id"
                                            value="Inventory asset account (trial balance)"
                                        />
                                        <select
                                            id="inventory_chart_account_id"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.inventory_chart_account_id}
                                            onChange={(e) =>
                                                setData(
                                                    'inventory_chart_account_id',
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                Not linked (optional)
                                            </option>
                                            {inventoryAccountOptions.map((opt) => (
                                                <option
                                                    key={opt.id}
                                                    value={String(opt.id)}
                                                >
                                                    {opt.label}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-1 text-xs text-gray-500">
                                            Stock value from inventory is shown
                                            on the trial balance against this
                                            account label when quantities and
                                            unit costs are set.
                                        </p>
                                        <InputError
                                            className="mt-2"
                                            message={
                                                errors.inventory_chart_account_id
                                            }
                                        />
                                    </div>
                                )}

                                <div className="border-t border-gray-200 pt-8 mt-8">
                                    <header>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Accounting controls
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Lock journals through a closing date
                                            to prevent back-dated approvals.
                                        </p>
                                    </header>
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel
                                                htmlFor="journal_lock_date"
                                                value="Journal lock date"
                                            />
                                            <TextInput
                                                id="journal_lock_date"
                                                type="date"
                                                readOnly
                                                className="mt-1 block w-full"
                                                value={data.journal_lock_date}
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                Use close/reopen workflow below
                                                to change period lock with
                                                mandatory reason.
                                            </p>
                                        </div>
                                        <div>
                                            <InputLabel value="Next posted journal number" />
                                            <TextInput
                                                readOnly
                                                className="mt-1 block w-full bg-gray-50"
                                                value={String(
                                                    company.next_journal_posted_number ??
                                                        1,
                                                )}
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                Automatically increments when
                                                a journal is approved.
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-4 max-w-sm">
                                        <InputLabel
                                            htmlFor="dual_approval_threshold"
                                            value="Dual approval threshold"
                                        />
                                        <TextInput
                                            id="dual_approval_threshold"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            className="mt-1 block w-full"
                                            value={data.dual_approval_threshold}
                                            onChange={(e) =>
                                                setData(
                                                    'dual_approval_threshold',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Leave empty to disable"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Journals at or above this amount
                                            require two different approvers.
                                        </p>
                                        <InputError
                                            className="mt-1"
                                            message={
                                                errors.dual_approval_threshold
                                            }
                                        />
                                    </div>
                                    <div className="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                                        <p>
                                            <span className="font-medium">
                                                Current close reason:
                                            </span>{' '}
                                            {company.journal_lock_reason || '—'}
                                        </p>
                                        <p className="mt-1">
                                            <span className="font-medium">
                                                Last change:
                                            </span>{' '}
                                            {company.journal_lock_updated_at
                                                ? formatDisplayDateTime(
                                                      company.journal_lock_updated_at,
                                                  )
                                                : '—'}
                                            {company.journal_lock_updated_by_name
                                                ? ` by ${company.journal_lock_updated_by_name}`
                                                : ''}
                                        </p>
                                    </div>

                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                closePeriodForm.post(
                                                    route('company.period.close'),
                                                    { preserveScroll: true },
                                                );
                                            }}
                                            className="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3"
                                        >
                                            <h4 className="text-sm font-semibold text-amber-900">
                                                Close / extend close
                                            </h4>
                                            <InputError
                                                className="mt-1"
                                                message={
                                                    closePeriodForm.errors
                                                        .close_checklist
                                                }
                                            />
                                            <div>
                                                <InputLabel
                                                    htmlFor="close_lock_date"
                                                    value="Close through date"
                                                />
                                                <TextInput
                                                    id="close_lock_date"
                                                    type="date"
                                                    className="mt-1 block w-full"
                                                    value={
                                                        closePeriodForm.data
                                                            .close_lock_date
                                                    }
                                                    onChange={(e) =>
                                                        closePeriodForm.setData(
                                                            'close_lock_date',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        closePeriodForm.errors
                                                            .close_lock_date
                                                    }
                                                />
                                            </div>
                                            <div>
                                                <InputLabel
                                                    htmlFor="close_reason"
                                                    value="Reason"
                                                />
                                                <textarea
                                                    id="close_reason"
                                                    rows={3}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value={
                                                        closePeriodForm.data
                                                            .close_reason
                                                    }
                                                    onChange={(e) =>
                                                        closePeriodForm.setData(
                                                            'close_reason',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        closePeriodForm.errors
                                                            .close_reason
                                                    }
                                                />
                                            </div>
                                            <PrimaryButton
                                                disabled={
                                                    closePeriodForm.processing
                                                }
                                            >
                                                Save period close
                                            </PrimaryButton>
                                        </form>

                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                reopenPeriodForm.post(
                                                    route(
                                                        'company.period.reopen',
                                                    ),
                                                    { preserveScroll: true },
                                                );
                                            }}
                                            className="space-y-3 rounded-md border border-indigo-200 bg-indigo-50 p-3"
                                        >
                                            <h4 className="text-sm font-semibold text-indigo-900">
                                                Reopen period
                                            </h4>
                                            <div>
                                                <InputLabel
                                                    htmlFor="reopen_to_date"
                                                    value="Reopen to date (optional)"
                                                />
                                                <TextInput
                                                    id="reopen_to_date"
                                                    type="date"
                                                    className="mt-1 block w-full"
                                                    value={
                                                        reopenPeriodForm.data
                                                            .reopen_to_date
                                                    }
                                                    onChange={(e) =>
                                                        reopenPeriodForm.setData(
                                                            'reopen_to_date',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <p className="mt-1 text-xs text-gray-500">
                                                    Leave empty to fully remove
                                                    close lock.
                                                </p>
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        reopenPeriodForm.errors
                                                            .reopen_to_date
                                                    }
                                                />
                                            </div>
                                            <div>
                                                <InputLabel
                                                    htmlFor="reopen_reason"
                                                    value="Reason"
                                                />
                                                <textarea
                                                    id="reopen_reason"
                                                    rows={3}
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value={
                                                        reopenPeriodForm.data
                                                            .reopen_reason
                                                    }
                                                    onChange={(e) =>
                                                        reopenPeriodForm.setData(
                                                            'reopen_reason',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        reopenPeriodForm.errors
                                                            .reopen_reason
                                                    }
                                                />
                                            </div>
                                            <PrimaryButton
                                                disabled={
                                                    reopenPeriodForm.processing
                                                }
                                            >
                                                Reopen period
                                            </PrimaryButton>
                                        </form>
                                    </div>
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
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
