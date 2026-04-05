import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Edit({
    activity,
    typeLabels,
    companies,
    currentCompanyId,
}) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, put, processing, errors } = useForm({
        type: activity.type,
        title: activity.title,
        body: activity.body ?? '',
        due_at: activity.due_at ?? '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const submit = (e) => {
        e.preventDefault();
        put(
            route('crm.activities.update', {
                activity: activity.id,
            }),
        );
    };

    const q =
        isAdmin && currentCompanyId ? { company_id: currentCompanyId } : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Edit activity
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="crm.activities.edit"
                        routeParams={{ activity: activity.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Edit activity" />
            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Linked to:{' '}
                        <strong>{activity.subject_label}</strong> (
                        {activity.subject_kind})
                    </p>
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
                            <InputLabel htmlFor="due_at" value="Due" />
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
                                Update
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
