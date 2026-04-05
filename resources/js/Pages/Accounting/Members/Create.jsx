import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Create({ companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        reference_code: '',
        name: '',
        email: '',
        phone: '',
        address: '',
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
        post(route('members.store'));
    };

    const companyQuery =
        isAdmin && currentCompanyId
            ? { company_id: currentCompanyId }
            : {};

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Register member
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="members.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="Register member" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-600">
                        This creates a <strong>pending</strong> member. A company
                        approver must approve before they can be used on loan or
                        savings finance records.
                    </p>
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id || currentCompanyId}
                            />
                        )}
                        <div>
                            <InputLabel
                                htmlFor="name"
                                value="Full name"
                            />
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
                            <InputLabel
                                htmlFor="reference_code"
                                value="Reference code (optional)"
                            />
                            <TextInput
                                id="reference_code"
                                className="mt-1 block w-full"
                                value={data.reference_code}
                                onChange={(e) =>
                                    setData('reference_code', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.reference_code}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                className="mt-1 block w-full"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                            />
                            <InputError message={errors.phone} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="address" value="Address" />
                            <textarea
                                id="address"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                            />
                            <InputError
                                message={errors.address}
                                className="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="notes" value="Notes" />
                            <textarea
                                id="notes"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError message={errors.notes} className="mt-2" />
                        </div>

                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>Submit</PrimaryButton>
                            <Link
                                href={route('members.index', companyQuery)}
                                className="inline-flex items-center text-sm text-gray-600 underline"
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
