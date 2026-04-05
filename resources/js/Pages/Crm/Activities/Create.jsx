import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

export default function Create({
    accounts,
    contacts,
    opportunities,
    typeLabels,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        type: 'task',
        subject_kind: 'crm_account',
        subject_id: '',
        title: '',
        body: '',
        due_at: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const subjectOptions = useMemo(() => {
        if (data.subject_kind === 'crm_account') {
            return accounts.map((a) => ({ id: a.id, label: a.name }));
        }
        if (data.subject_kind === 'crm_contact') {
            return contacts.map((c) => ({
                id: c.id,
                label: `${c.first_name} ${c.last_name}`,
            }));
        }
        return opportunities.map((o) => ({ id: o.id, label: o.name }));
    }, [data.subject_kind, accounts, contacts, opportunities]);

    useEffect(() => {
        setData('subject_id', '');
    }, [data.subject_kind, setData]);

    const submit = (e) => {
        e.preventDefault();
        post(route('crm.activities.store'));
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Log activity
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.activities.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Log CRM activity" />
            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg dark:bg-gray-900"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                value={data.company_id || currentCompanyId}
                            />
                        )}
                        <div>
                            <InputLabel htmlFor="type" value="Type" />
                            <select
                                id="type"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value)
                                }
                            >
                                {Object.entries(typeLabels).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.type} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="subject_kind"
                                value="Related record"
                            />
                            <select
                                id="subject_kind"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.subject_kind}
                                onChange={(e) =>
                                    setData('subject_kind', e.target.value)
                                }
                            >
                                <option value="crm_account">Account</option>
                                <option value="crm_contact">Contact</option>
                                <option value="crm_opportunity">
                                    Opportunity
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="subject_id" value="Choose" />
                            <select
                                id="subject_id"
                                required
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.subject_id}
                                onChange={(e) =>
                                    setData('subject_id', e.target.value)
                                }
                            >
                                <option value="">— Select —</option>
                                {subjectOptions.map((o) => (
                                    <option key={o.id} value={o.id}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.subject_id}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="title" value="Title" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.title}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="body" value="Details" />
                            <textarea
                                id="body"
                                rows={4}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.body}
                                onChange={(e) =>
                                    setData('body', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="due_at"
                                value="Due date/time (optional)"
                            />
                            <TextInput
                                id="due_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
                                value={data.due_at}
                                onChange={(e) =>
                                    setData('due_at', e.target.value)
                                }
                            />
                        </div>
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('crm.activities.index', q)}
                                className="text-sm text-gray-600 underline"
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
