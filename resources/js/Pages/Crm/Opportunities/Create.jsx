import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Create({
    accounts,
    contacts,
    owners,
    stageLabels,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        stage: 'lead',
        amount: '',
        probability: '',
        expected_close_date: '',
        crm_account_id: '',
        crm_contact_id: '',
        owner_user_id: '',
        notes: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        post(route('crm.opportunities.store'));
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        New opportunity
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.opportunities.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New opportunity" />
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
                            <InputLabel htmlFor="name" value="Opportunity name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="stage" value="Stage" />
                            <select
                                id="stage"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.stage}
                                onChange={(e) =>
                                    setData('stage', e.target.value)
                                }
                            >
                                {Object.entries(stageLabels).map(([k, v]) => (
                                    <option key={k} value={k}>
                                        {v}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.stage}
                                className="mt-2"
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="amount"
                                    value="Amount (optional)"
                                />
                                <TextInput
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.amount}
                                    onChange={(e) =>
                                        setData('amount', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.amount}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="probability"
                                    value="Probability % (optional)"
                                />
                                <TextInput
                                    id="probability"
                                    type="number"
                                    min="0"
                                    max="100"
                                    className="mt-1 block w-full"
                                    value={data.probability}
                                    onChange={(e) =>
                                        setData('probability', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="expected_close_date"
                                value="Expected close (optional)"
                            />
                            <TextInput
                                id="expected_close_date"
                                type="date"
                                className="mt-1 block w-full"
                                value={data.expected_close_date}
                                onChange={(e) =>
                                    setData(
                                        'expected_close_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="crm_account_id"
                                value="Account (optional)"
                            />
                            <select
                                id="crm_account_id"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.crm_account_id}
                                onChange={(e) =>
                                    setData('crm_account_id', e.target.value)
                                }
                            >
                                <option value="">— None —</option>
                                {accounts.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="crm_contact_id"
                                value="Contact (optional)"
                            />
                            <select
                                id="crm_contact_id"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.crm_contact_id}
                                onChange={(e) =>
                                    setData('crm_contact_id', e.target.value)
                                }
                            >
                                <option value="">— None —</option>
                                {contacts.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.first_name} {c.last_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="owner_user_id"
                                value="Owner (optional)"
                            />
                            <select
                                id="owner_user_id"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.owner_user_id}
                                onChange={(e) =>
                                    setData('owner_user_id', e.target.value)
                                }
                            >
                                <option value="">— Unassigned —</option>
                                {owners.map((o) => (
                                    <option key={o.id} value={o.id}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="notes" value="Notes" />
                            <textarea
                                id="notes"
                                rows={3}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                        </div>
                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('crm.opportunities.index', q)}
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
