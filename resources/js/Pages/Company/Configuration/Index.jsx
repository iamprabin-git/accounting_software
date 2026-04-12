import Checkbox from '@/Components/Checkbox';
import CompanyWorkspaceSidebar from '@/Components/CompanyWorkspaceSidebar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import axios from 'axios';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function Index({
    company,
    inventoryAccountOptions = [],
    liabilityAccountOptions = [],
}) {
    const page = usePage();
    const { t } = useTranslation();
    const canManageCompanySettings = Boolean(
        page.props.auth?.user?.can_manage_company_settings,
    );

    const backup = company.backup ?? {};
    const cbs = company.cbs ?? {};
    const initialSnapshots =
        backup.recorded_snapshots?.length > 0
            ? backup.recorded_snapshots.map((r) => ({
                  snapshot_date: r.snapshot_date || '',
                  label: r.label || '',
                  path_or_filename: r.path_or_filename || '',
              }))
            : [
                  {
                      snapshot_date: '',
                      label: '',
                      path_or_filename: '',
                  },
              ];
    const holidaysQuery =
        page.props.auth?.user?.role === 'admin' &&
        page.props.current_company_id
            ? { company_id: page.props.current_company_id }
            : {};

    const backupZipQuery =
        page.props.auth?.user?.role === 'admin' &&
        page.props.current_company_id
            ? { company_id: page.props.current_company_id }
            : {};

    const [backupDownloadBusy, setBackupDownloadBusy] = useState(false);
    const [backupDownloadError, setBackupDownloadError] = useState('');

    const downloadPortableBackupZip = async () => {
        setBackupDownloadError('');
        setBackupDownloadBusy(true);
        try {
            const res = await axios.post(
                route(
                    'company.configuration.portable-backup-zip',
                    backupZipQuery,
                ),
                {},
                {
                    responseType: 'blob',
                    withCredentials: true,
                },
            );
            const cd = res.headers['content-disposition'];
            let filename = `company-${company.id}-backup.zip`;
            if (typeof cd === 'string') {
                const m = cd.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                if (m?.[1]) {
                    filename = decodeURIComponent(m[1].replace(/["']/g, ''));
                }
            }
            const url = URL.createObjectURL(res.data);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        } catch {
            setBackupDownloadError(
                t('companyWorkspace.downloadPortableBackupZipFailed'),
            );
        } finally {
            setBackupDownloadBusy(false);
        }
    };

    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm({
            enforce_holiday_blackout: Boolean(
                cbs.enforce_holiday_blackout ?? true,
            ),
            cbs_internal_notes: cbs.internal_notes || '',
            dual_approval_threshold:
                company.dual_approval_threshold != null
                    ? String(company.dual_approval_threshold)
                    : '',
            inventory_chart_account_id:
                company.inventory_chart_account_id != null
                    ? String(company.inventory_chart_account_id)
                    : '',
            backup_snapshots_root_folder: backup.snapshots_root_folder || '',
            backup_restore_instructions: backup.restore_instructions || '',
            backup_recorded_snapshots: initialSnapshots,
            deposit_interest_withholding_tax_percent:
                cbs.deposit_interest_withholding_tax_percent != null &&
                cbs.deposit_interest_withholding_tax_percent !== ''
                    ? String(cbs.deposit_interest_withholding_tax_percent)
                    : '0',
            deposit_interest_tax_payable_chart_account_id:
                cbs.deposit_interest_tax_payable_chart_account_id != null
                    ? String(cbs.deposit_interest_tax_payable_chart_account_id)
                    : '',
        });

    const closePeriodForm = useForm({
        close_lock_date: company.journal_lock_date || '',
        close_reason: '',
        close_type: 'custom',
        retained_earnings_account_id: '',
    });
    const reopenPeriodForm = useForm({
        reopen_to_date: '',
        reopen_reason: '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (!canManageCompanySettings) {
            return;
        }
        post(route('company.configuration.update'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {t('nav.companyConfiguration')}
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            {t('companyWorkspace.configurationPageSubtitle')}
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
            <Head title={t('nav.companyConfiguration')} />

            <div className="py-8 sm:py-12">
                <div className="mx-auto flex w-full min-w-0 max-w-6xl flex-col gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:gap-10 lg:px-8">
                    <CompanyWorkspaceSidebar />
                    <div className="min-w-0 flex-1">
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-6 lg:p-8">
                        <section>
                            {!canManageCompanySettings ? (
                                <div className="mb-6 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                                    {t('companyWorkspace.readOnlySettingsHint')}
                                </div>
                            ) : null}

                            <div className="border-t border-gray-200 pt-8">
                                <header>
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Journal period lock
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-600">
                                        Close through a date to block
                                        back-dated journal approvals, or reopen
                                        with a recorded reason. Requires
                                        permission to approve journals.
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
                                            value={
                                                company.journal_lock_date || ''
                                            }
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Use close/reopen below to change the
                                            lock (reason required).
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
                                            Increments when a journal is
                                            approved.
                                        </p>
                                    </div>
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

                                {canManageCompanySettings ? (
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
                                                htmlFor="close_type"
                                                value="Close type"
                                            />
                                            <select
                                                id="close_type"
                                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    closePeriodForm.data
                                                        .close_type
                                                }
                                                onChange={(e) =>
                                                    closePeriodForm.setData(
                                                        'close_type',
                                                        e.target.value,
                                                    )
                                                }
                                            >
                                                <option value="custom">
                                                    Custom
                                                </option>
                                                <option value="month_end">
                                                    Month end (BS — last day of Bikram
                                                    Sambat month)
                                                </option>
                                                <option value="quarter_end">
                                                    Quarter end (Nepali fiscal — last day
                                                    of Ashwin, Paush, Chaitra, or Ashadh)
                                                </option>
                                                <option value="year_end">
                                                    Year end (BS — last day of Chaitra)
                                                </option>
                                                <option value="fiscal_year_end">
                                                    Fiscal year end (last day of Ashadh,
                                                    P&amp;L to retained earnings)
                                                </option>
                                            </select>
                                            <InputError
                                                className="mt-1"
                                                message={
                                                    closePeriodForm.errors
                                                        .close_type
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
                                        {closePeriodForm.data.close_type ===
                                        'fiscal_year_end' ? (
                                            <div>
                                                <InputLabel
                                                    htmlFor="retained_earnings_account_id"
                                                    value="Retained earnings account ID (optional)"
                                                />
                                                <TextInput
                                                    id="retained_earnings_account_id"
                                                    type="number"
                                                    className="mt-1 block w-full"
                                                    value={
                                                        closePeriodForm.data
                                                            .retained_earnings_account_id
                                                    }
                                                    onChange={(e) =>
                                                        closePeriodForm.setData(
                                                            'retained_earnings_account_id',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Auto-detect by account name if empty"
                                                />
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        closePeriodForm.errors
                                                            .retained_earnings_account_id
                                                    }
                                                />
                                            </div>
                                        ) : null}
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
                                                Leave empty to fully remove close
                                                lock.
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
                                ) : null}
                            </div>

                            <form
                                onSubmit={submit}
                                className="mt-10 space-y-8 border-t border-gray-200 pt-8"
                            >
                                <fieldset
                                    disabled={!canManageCompanySettings}
                                    className={
                                        'min-w-0 border-0 p-0 ' +
                                        (!canManageCompanySettings
                                            ? 'opacity-[0.72]'
                                            : '')
                                    }
                                >
                                <div className="rounded-lg border border-gray-200 p-4">
                                    <div className="flex items-start gap-2">
                                        <Checkbox
                                            id="enforce_holiday_blackout"
                                            name="enforce_holiday_blackout"
                                            checked={
                                                data.enforce_holiday_blackout
                                            }
                                            onChange={(e) =>
                                                setData(
                                                    'enforce_holiday_blackout',
                                                    e.target.checked,
                                                )
                                            }
                                        />
                                        <div>
                                            <InputLabel
                                                htmlFor="enforce_holiday_blackout"
                                                value="Enforce holiday blackout for transactions"
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                Postings must use a working day:
                                                weekdays except company holidays,
                                                or weekends you mark as working on
                                                the Holidays calendar. The server
                                                enforces this for journals, cash,
                                                finance postings, and inventory.
                                                This checkbox is still saved for
                                                compatibility; date picking in the
                                                app follows the working calendar.
                                            </p>
                                            <p className="mt-2 text-sm text-gray-600">
                                                <Link
                                                    href={route(
                                                        'company.holidays.index',
                                                        holidaysQuery,
                                                    )}
                                                    className="font-medium text-indigo-600 underline hover:text-indigo-800"
                                                >
                                                    {t('nav.companyHolidays')}
                                                </Link>
                                                {' — '}
                                                open the calendar to add or
                                                remove dates.
                                            </p>
                                        </div>
                                    </div>
                                    <div className="mt-4">
                                        <InputLabel
                                            htmlFor="cbs_internal_notes"
                                            value="Internal notes (CBS / operations)"
                                        />
                                        <textarea
                                            id="cbs_internal_notes"
                                            rows={4}
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={data.cbs_internal_notes}
                                            onChange={(e) =>
                                                setData(
                                                    'cbs_internal_notes',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Policies, escalation contacts, or reminders for your team…"
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.cbs_internal_notes}
                                        />
                                    </div>
                                    <div className="mt-6 border-t border-gray-200 pt-6">
                                        <h4 className="text-sm font-medium text-gray-900">
                                            Deposit interest withholding (tax)
                                        </h4>
                                        <p className="mt-1 text-xs text-gray-500">
                                            When posting quarterly savings interest,
                                            this percentage is withheld to a
                                            liability; the member deposit is
                                            credited net of tax. Set to 0 to
                                            disable.
                                        </p>
                                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <InputLabel
                                                    htmlFor="deposit_interest_withholding_tax_percent"
                                                    value="Withholding rate (%)"
                                                />
                                                <TextInput
                                                    id="deposit_interest_withholding_tax_percent"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    className="mt-1 block w-full"
                                                    value={
                                                        data.deposit_interest_withholding_tax_percent
                                                    }
                                                    onChange={(e) =>
                                                        setData(
                                                            'deposit_interest_withholding_tax_percent',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    className="mt-1"
                                                    message={
                                                        errors.deposit_interest_withholding_tax_percent
                                                    }
                                                />
                                            </div>
                                            {liabilityAccountOptions.length >
                                            0 ? (
                                                <div>
                                                    <InputLabel
                                                        htmlFor="deposit_interest_tax_payable_chart_account_id"
                                                        value="Tax payable liability account"
                                                    />
                                                    <select
                                                        id="deposit_interest_tax_payable_chart_account_id"
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        value={
                                                            data.deposit_interest_tax_payable_chart_account_id
                                                        }
                                                        onChange={(e) =>
                                                            setData(
                                                                'deposit_interest_tax_payable_chart_account_id',
                                                                e.target.value,
                                                            )
                                                        }
                                                    >
                                                        <option value="">
                                                            Select if rate is
                                                            above zero…
                                                        </option>
                                                        {liabilityAccountOptions.map(
                                                            (a) => (
                                                                <option
                                                                    key={a.id}
                                                                    value={a.id}
                                                                >
                                                                    {a.label}
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <InputError
                                                        className="mt-1"
                                                        message={
                                                            errors.deposit_interest_tax_payable_chart_account_id
                                                        }
                                                    />
                                                </div>
                                            ) : (
                                                <p className="self-end text-sm text-amber-800">
                                                    Add an approved liability
                                                    chart account to select a tax
                                                    payable account.
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
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
                                    {inventoryAccountOptions.length > 0 ? (
                                        <div>
                                            <InputLabel
                                                htmlFor="inventory_chart_account_id"
                                                value="Inventory asset account (trial balance)"
                                            />
                                            <select
                                                id="inventory_chart_account_id"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    data.inventory_chart_account_id
                                                }
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
                                                {inventoryAccountOptions.map(
                                                    (opt) => (
                                                        <option
                                                            key={opt.id}
                                                            value={String(
                                                                opt.id,
                                                            )}
                                                        >
                                                            {opt.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <p className="mt-1 text-xs text-gray-500">
                                                Stock value rolls into the trial
                                                balance on this account when
                                                quantities and costs are set.
                                            </p>
                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors.inventory_chart_account_id
                                                }
                                            />
                                        </div>
                                    ) : null}
                                </div>

                                <div className="border-t border-gray-200 pt-8">
                                    <header>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Backup &amp; restore (after mistakes)
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Record where database snapshots live
                                            and how to restore. The application
                                            does not dump the database for you;
                                            use mysqldump or your host&apos;s
                                            backup tools and store files in the
                                            folder you document here.
                                        </p>
                                    </header>
                                        <p className="mt-3 rounded-md bg-slate-50 p-2 text-xs text-gray-600">
                                        Suggested folder on server:{' '}
                                        <code className="rounded bg-white px-1 py-0.5 ring-1 ring-gray-200">
                                            {backup.suggested_root}
                                        </code>
                                    </p>
                                    <p className="mt-2 text-xs leading-relaxed text-gray-600">
                                        <span className="font-medium text-gray-800">
                                            Portable company database (USB / transfer):
                                        </span>{' '}
                                        A daily SQLite file for{' '}
                                        <em>this</em> organization is written under{' '}
                                        <code className="rounded bg-white px-1 py-0.5 ring-1 ring-gray-200">
                                            storage/app/company-portable-databases/
                                            {company.id}/YYYY-MM-DD.sqlite
                                        </code>{' '}
                                        when the server scheduler runs, or run manually:{' '}
                                        <code className="rounded bg-white px-1 py-0.5 ring-1 ring-gray-200">
                                            php artisan
                                            company:write-daily-portable-database{' '}
                                            {company.id}
                                        </code>
                                        . Copy that folder to an external drive or another
                                        PC and open the <code>.sqlite</code> file with any
                                        SQLite viewer.                                         This is a data extract for backup
                                        and analysis, not a full app reinstall image.
                                    </p>
                                    {canManageCompanySettings ? (
                                        <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                            <button
                                                type="button"
                                                disabled={backupDownloadBusy}
                                                onClick={() =>
                                                    downloadPortableBackupZip()
                                                }
                                                className="inline-flex touch-manipulation items-center justify-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                {backupDownloadBusy
                                                    ? t(
                                                          'companyWorkspace.downloadPortableBackupZipPreparing',
                                                      )
                                                    : t(
                                                          'companyWorkspace.downloadPortableBackupZip',
                                                      )}
                                            </button>
                                            <span className="text-xs text-gray-500">
                                                Refreshes today&apos;s snapshot,
                                                then downloads every{' '}
                                                <code className="rounded bg-gray-100 px-1">
                                                    .sqlite
                                                </code>{' '}
                                                and{' '}
                                                <code className="rounded bg-gray-100 px-1">
                                                    manifest.json
                                                </code>{' '}
                                                in a single ZIP (or the SQLite
                                                file alone if ZIP is unavailable
                                                on the server).
                                            </span>
                                        </div>
                                    ) : null}
                                    {backupDownloadError ? (
                                        <p className="mt-2 text-sm text-red-600">
                                            {backupDownloadError}
                                        </p>
                                    ) : null}
                                    <div className="mt-4 space-y-4">
                                        <div>
                                            <InputLabel
                                                htmlFor="backup_snapshots_root_folder"
                                                value="Snapshots root folder (path)"
                                            />
                                            <TextInput
                                                id="backup_snapshots_root_folder"
                                                className="mt-1 block w-full"
                                                value={
                                                    data.backup_snapshots_root_folder
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        'backup_snapshots_root_folder',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="e.g. storage/app/company-backups/1"
                                            />
                                            <InputError
                                                className="mt-1"
                                                message={
                                                    errors.backup_snapshots_root_folder
                                                }
                                            />
                                        </div>
                                        <div>
                                            <InputLabel
                                                htmlFor="backup_restore_instructions"
                                                value="Restore instructions (for your team)"
                                            />
                                            <textarea
                                                id="backup_restore_instructions"
                                                rows={3}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    data.backup_restore_instructions
                                                }
                                                onChange={(e) =>
                                                    setData(
                                                        'backup_restore_instructions',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="How to restore from a dated snapshot…"
                                            />
                                            <InputError
                                                className="mt-1"
                                                message={
                                                    errors.backup_restore_instructions
                                                }
                                            />
                                        </div>
                                        <div>
                                            <div className="flex items-center justify-between gap-2">
                                                <InputLabel value="Recorded snapshots (reference log)" />
                                                <button
                                                    type="button"
                                                    className="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                                    onClick={() =>
                                                        setData(
                                                            'backup_recorded_snapshots',
                                                            [
                                                                ...data.backup_recorded_snapshots,
                                                                {
                                                                    snapshot_date:
                                                                        '',
                                                                    label: '',
                                                                    path_or_filename:
                                                                        '',
                                                                },
                                                            ],
                                                        )
                                                    }
                                                >
                                                    Add row
                                                </button>
                                            </div>
                                            <div className="mt-2 space-y-2">
                                                {data.backup_recorded_snapshots.map(
                                                    (row, i) => (
                                                        <div
                                                            key={i}
                                                            className="grid gap-2 rounded-md border border-gray-200 p-2 sm:grid-cols-3"
                                                        >
                                                            <div>
                                                                <TextInput
                                                                    type="date"
                                                                    className="block w-full"
                                                                    value={
                                                                        row.snapshot_date
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) => {
                                                                        const next =
                                                                            [
                                                                                ...data.backup_recorded_snapshots,
                                                                            ];
                                                                        next[
                                                                            i
                                                                        ] = {
                                                                            ...next[
                                                                                i
                                                                            ],
                                                                            snapshot_date:
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                        };
                                                                        setData(
                                                                            'backup_recorded_snapshots',
                                                                            next,
                                                                        );
                                                                    }}
                                                                />
                                                                <InputError
                                                                    className="mt-1"
                                                                    message={
                                                                        errors[
                                                                            `backup_recorded_snapshots.${i}.snapshot_date`
                                                                        ]
                                                                    }
                                                                />
                                                            </div>
                                                            <TextInput
                                                                placeholder="Label"
                                                                className="block w-full"
                                                                value={row.label}
                                                                onChange={(e) => {
                                                                    const next =
                                                                        [
                                                                            ...data.backup_recorded_snapshots,
                                                                        ];
                                                                    next[i] = {
                                                                        ...next[
                                                                            i
                                                                        ],
                                                                        label: e
                                                                            .target
                                                                            .value,
                                                                    };
                                                                    setData(
                                                                        'backup_recorded_snapshots',
                                                                        next,
                                                                    );
                                                                }}
                                                            />
                                                            <div className="flex gap-2">
                                                                <TextInput
                                                                    placeholder="File / path"
                                                                    className="block w-full"
                                                                    value={
                                                                        row.path_or_filename
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) => {
                                                                        const next =
                                                                            [
                                                                                ...data.backup_recorded_snapshots,
                                                                            ];
                                                                        next[
                                                                            i
                                                                        ] = {
                                                                            ...next[
                                                                                i
                                                                            ],
                                                                            path_or_filename:
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                        };
                                                                        setData(
                                                                            'backup_recorded_snapshots',
                                                                            next,
                                                                        );
                                                                    }}
                                                                />
                                                                <button
                                                                    type="button"
                                                                    className="shrink-0 text-xs text-red-600 hover:text-red-800"
                                                                    onClick={() =>
                                                                        setData(
                                                                            'backup_recorded_snapshots',
                                                                            data.backup_recorded_snapshots.filter(
                                                                                (
                                                                                    _,
                                                                                    j,
                                                                                ) =>
                                                                                    j !==
                                                                                    i,
                                                                            ),
                                                                        )
                                                                    }
                                                                >
                                                                    Remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4 border-t border-gray-200 pt-6">
                                    <PrimaryButton disabled={processing}>
                                        Save configuration
                                    </PrimaryButton>
                                    {recentlySuccessful ? (
                                        <span className="text-sm text-gray-600">
                                            Saved.
                                        </span>
                                    ) : null}
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
