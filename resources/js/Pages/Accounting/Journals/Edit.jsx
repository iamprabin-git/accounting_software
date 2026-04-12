import CompanyPicker from '@/Components/CompanyPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import WorkDatePicker from '@/Components/WorkDatePicker';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const emptyLine = () => ({
    chart_account_id: '',
    debit: '',
    credit: '',
    description: '',
});

export default function Edit({ journal, accounts, companies, currentCompanyId }) {
    const user = usePage().props.auth.user ?? {};
    const isAdmin = user.role === 'admin';

    const { data, setData, put, processing, errors } = useForm({
        reference: journal.reference || '',
        memo: journal.memo || '',
        transaction_date: journal.transaction_date,
        company_id: isAdmin ? String(currentCompanyId) : '',
        lines: journal.lines.map((l) => ({
            chart_account_id: String(l.chart_account_id),
            debit: l.debit > 0 ? String(l.debit) : '',
            credit: l.credit > 0 ? String(l.credit) : '',
            description: l.description || '',
        })),
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
        put(route('journals.update', { journal: journal.id }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Edit journal #{journal.id}
                    </h2>
                    <CompanyPicker
                        companies={companies}
                        currentCompanyId={currentCompanyId}
                        routeName="journals.edit"
                        routeParams={{ journal: journal.id }}
                        query={{}}
                    />
                </div>
            }
        >
            <Head title={`Edit journal ${journal.id}`} />

            <div className="py-8">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <Card>
                        <CardContent className="p-6 sm:p-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6"
                    >
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={data.company_id || currentCompanyId}
                            />
                        )}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <WorkDatePicker
                                    id="transaction_date"
                                    label="Transaction date"
                                    value={data.transaction_date}
                                    onChange={(iso) =>
                                        setData('transaction_date', iso)
                                    }
                                    error={errors.transaction_date}
                                    required
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
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Memo" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.memo}
                                onChange={(e) => setData('memo', e.target.value)}
                            />
                        </div>

                        <div>
                            <div className="mb-2 flex items-center justify-between">
                                <InputLabel value="Lines" />
                                <button
                                    type="button"
                                    className="text-sm text-indigo-600"
                                    onClick={addLine}
                                >
                                    + Add line
                                </button>
                            </div>
                            <InputError message={errors.lines} className="mb-2" />

                            <div className="overflow-x-auto rounded border">
                                <table className="min-w-full divide-y text-sm">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-2 py-2 text-left">
                                                Account
                                            </th>
                                            <th className="px-2 py-2 text-right">
                                                Debit
                                            </th>
                                            <th className="px-2 py-2 text-right">
                                                Credit
                                            </th>
                                            <th className="px-2 py-2 text-left">
                                                Description
                                            </th>
                                            <th className="px-2 py-2" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {data.lines.map((line, idx) => (
                                            <tr key={idx}>
                                                <td className="px-2 py-2">
                                                    <select
                                                        className="w-full rounded border-gray-300"
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
                                                            Select
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
                                                <td className="px-2 py-2">
                                                    <button
                                                        type="button"
                                                        className="text-red-600"
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

                        <div className="flex flex-wrap items-center gap-3">
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={route('journals.show', {
                                    journal: journal.id,
                                    company_id: isAdmin
                                        ? currentCompanyId
                                        : undefined,
                                })}
                            >
                                Cancel
                            </Link>
                            </Button>
                        </div>
                    </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
