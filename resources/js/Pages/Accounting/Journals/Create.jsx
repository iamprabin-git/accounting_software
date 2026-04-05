import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const emptyLine = () => ({
    chart_account_id: '',
    debit: '',
    credit: '',
    description: '',
});

export default function Create({ accounts, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, post, processing, errors } = useForm({
        reference: '',
        memo: '',
        transaction_date: new Date().toISOString().slice(0, 10),
        company_id: isAdmin ? currentCompanyId : '',
        lines: [emptyLine(), emptyLine()],
    });

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            setData('company_id', currentCompanyId);
        }
    }, [currentCompanyId, isAdmin, setData]);

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (idx) => {
        if (data.lines.length <= 2) return;
        setData(
            'lines',
            data.lines.filter((_, i) => i !== idx),
        );
    };

    const updateLine = (idx, field, value) => {
        const next = data.lines.map((line, i) =>
            i === idx ? { ...line, [field]: value } : line,
        );
        setData('lines', next);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('journals.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        New journal entry
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="journals.create"
                        routeParams={{}}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title="New journal" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow sm:rounded-lg"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id}
                            />
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Transaction date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.transaction_date}
                                    onChange={(e) =>
                                        setData(
                                            'transaction_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.transaction_date}
                                    className="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel value="Reference" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.reference}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Memo" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.memo}
                                onChange={(e) => setData('memo', e.target.value)}
                            />
                            <InputError message={errors.memo} className="mt-2" />
                        </div>

                        <div>
                            <div className="mb-2 flex items-center justify-between">
                                <InputLabel value="Lines (debits must equal credits)" />
                                <button
                                    type="button"
                                    className="text-sm text-indigo-600 hover:text-indigo-800"
                                    onClick={addLine}
                                >
                                    + Add line
                                </button>
                            </div>
                            <InputError message={errors.lines} className="mb-2" />

                            <div className="overflow-x-auto rounded border border-gray-200">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-2 py-2 text-left font-medium text-gray-600">
                                                Account
                                            </th>
                                            <th className="px-2 py-2 text-right font-medium text-gray-600">
                                                Debit
                                            </th>
                                            <th className="px-2 py-2 text-right font-medium text-gray-600">
                                                Credit
                                            </th>
                                            <th className="px-2 py-2 text-left font-medium text-gray-600">
                                                Description
                                            </th>
                                            <th className="px-2 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {data.lines.map((line, idx) => (
                                            <tr key={idx}>
                                                <td className="px-2 py-2">
                                                    <select
                                                        className="w-full rounded border-gray-300 text-sm"
                                                        value={line.chart_account_id}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'chart_account_id',
                                                                e.target.value,
                                                            )
                                                        }
                                                        required
                                                    >
                                                        <option value="">
                                                            Select account
                                                        </option>
                                                        {accounts.map((a) => (
                                                            <option
                                                                key={a.id}
                                                                value={a.id}
                                                            >
                                                                {a.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `lines.${idx}.chart_account_id`
                                                            ]
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <TextInput
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        className="w-full text-right"
                                                        value={line.debit}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'debit',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <TextInput
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        className="w-full text-right"
                                                        value={line.credit}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'credit',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2">
                                                    <TextInput
                                                        className="w-full"
                                                        value={line.description}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                idx,
                                                                'description',
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 py-2 text-right">
                                                    <button
                                                        type="button"
                                                        className="text-red-600 hover:text-red-800"
                                                        onClick={() =>
                                                            removeLine(idx)
                                                        }
                                                    >
                                                        ✕
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <PrimaryButton disabled={processing}>
                                Save draft
                            </PrimaryButton>
                            <Link
                                href={route('journals.index', {
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
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
