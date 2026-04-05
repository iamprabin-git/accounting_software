import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { moneyFromCents } from '@/utils/money';
import { useForm, router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

const VIEWS = {
    MENU: 'menu',
    DEPOSIT: 'deposit',
    WITHDRAW: 'withdraw',
    ADJUST: 'adjust',
    STATEMENT: 'statement',
    DISBURSE: 'disburse',
    INSTALLMENT: 'installment',
    PENALTY: 'penalty',
};

function todayIsoDate() {
    return new Date().toISOString().slice(0, 10);
}

function LedgerFields({ accounts, data, setData, errors, prefix = '' }) {
    const p = (name) => (prefix ? `${prefix}.${name}` : name);
    const err = (name) => errors[p(name)] ?? errors[name];
    return (
        <div className="grid gap-3 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <InputLabel value="Transaction date" />
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.transaction_date}
                    onChange={(e) => setData('transaction_date', e.target.value)}
                    required
                />
                <InputError message={err('transaction_date')} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Debit account" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.debit_chart_account_id}
                    onChange={(e) =>
                        setData('debit_chart_account_id', e.target.value)
                    }
                    required
                >
                    <option value="">Select…</option>
                    {accounts.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.label}
                        </option>
                    ))}
                </select>
                <InputError
                    message={err('debit_chart_account_id')}
                    className="mt-1"
                />
            </div>
            <div>
                <InputLabel value="Credit account" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    value={data.credit_chart_account_id}
                    onChange={(e) =>
                        setData('credit_chart_account_id', e.target.value)
                    }
                    required
                >
                    <option value="">Select…</option>
                    {accounts.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.label}
                        </option>
                    ))}
                </select>
                <InputError
                    message={err('credit_chart_account_id')}
                    className="mt-1"
                />
            </div>
            <div className="sm:col-span-2">
                <InputLabel value="Reference (optional)" />
                <TextInput
                    className="mt-1 block w-full"
                    value={data.reference}
                    onChange={(e) => setData('reference', e.target.value)}
                />
                <InputError message={err('reference')} className="mt-1" />
            </div>
        </div>
    );
}

export default function LoanSavingsProductModal({
    open,
    onClose,
    category,
    row,
    companyQuery,
    isAdmin,
    currentCompanyId,
    chartAccounts = [],
    onAfterMovementSuccess = null,
}) {
    const [view, setView] = useState(VIEWS.MENU);
    const [loading, setLoading] = useState(false);
    const [statementRows, setStatementRows] = useState([]);
    const [statementError, setStatementError] = useState('');

    const depositForm = useForm({
        amount: '',
        memo: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });
    const withdrawForm = useForm({
        amount: '',
        memo: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });
    const adjustForm = useForm({
        amount: '',
        memo: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    });

    const ledgerDefaults = {
        transaction_date: todayIsoDate(),
        debit_chart_account_id: '',
        credit_chart_account_id: '',
        reference: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
    };

    const disburseForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });
    const installmentForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });
    const penaltyForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });

    const svDepositForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });
    const svWithdrawForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });
    const svAdjustForm = useForm({
        amount: '',
        memo: '',
        ...ledgerDefaults,
    });

    const isStructuredLoan =
        category === 'loan' && Boolean(row?.uses_structured_loan);
    const loanReady = Boolean(row?.loan_operational);
    const isStructuredSavings =
        category === 'savings' && Boolean(row?.uses_structured_savings);
    const savingsReady = Boolean(row?.savings_operational);

    const loadStatement = useCallback(async () => {
        if (!row?.id) return;
        setLoading(true);
        setStatementError('');
        try {
            const url = new URL(
                route('finance.positions.movements-data', {
                    category,
                    position: row.id,
                }),
                window.location.origin,
            );
            if (isAdmin && currentCompanyId) {
                url.searchParams.set('company_id', String(currentCompanyId));
            }
            const res = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                throw new Error('Failed to load');
            }
            const data = await res.json();
            setStatementRows(data.movements ?? []);
        } catch {
            setStatementError('Could not load activity.');
            setStatementRows([]);
        } finally {
            setLoading(false);
        }
    }, [row?.id, category, isAdmin, currentCompanyId]);

    useEffect(() => {
        if (open && view === VIEWS.STATEMENT) {
            loadStatement();
        }
    }, [open, view, loadStatement]);

    useEffect(() => {
        if (!open) {
            setView(VIEWS.MENU);
            setStatementRows([]);
            depositForm.reset();
            withdrawForm.reset();
            adjustForm.reset();
            disburseForm.reset();
            installmentForm.reset();
            penaltyForm.reset();
            svDepositForm.reset();
            svWithdrawForm.reset();
            svAdjustForm.reset();
        }
    }, [open]);

    useEffect(() => {
        if (isAdmin && currentCompanyId) {
            const c = String(currentCompanyId);
            disburseForm.setData('company_id', c);
            installmentForm.setData('company_id', c);
            penaltyForm.setData('company_id', c);
            svDepositForm.setData('company_id', c);
            svWithdrawForm.setData('company_id', c);
            svAdjustForm.setData('company_id', c);
        }
    }, [currentCompanyId, isAdmin, open]);

    if (!open || !row) {
        return null;
    }

    const postOpts = {
        preserveScroll: true,
        onSuccess: () => {
            if (typeof onAfterMovementSuccess === 'function') {
                onAfterMovementSuccess();
            } else {
                router.reload({ only: ['positions', 'totals'] });
            }
            onClose();
        },
    };

    const submitDeposit = (e) => {
        e.preventDefault();
        depositForm.post(
            route('finance.positions.movements.deposit', {
                category,
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitWithdraw = (e) => {
        e.preventDefault();
        withdrawForm.post(
            route('finance.positions.movements.withdraw', {
                category,
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitAdjust = (e) => {
        e.preventDefault();
        adjustForm.post(
            route('finance.positions.movements.adjustment', {
                category,
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitDisburse = (e) => {
        e.preventDefault();
        disburseForm.post(
            route('finance.positions.movements.disburse', {
                category: 'loan',
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitInstallment = (e) => {
        e.preventDefault();
        installmentForm.post(
            route('finance.positions.movements.installment', {
                category: 'loan',
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitPenalty = (e) => {
        e.preventDefault();
        penaltyForm.post(
            route('finance.positions.movements.penalty', {
                category: 'loan',
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitSvDeposit = (e) => {
        e.preventDefault();
        svDepositForm.post(
            route('finance.positions.movements.savings-deposit', {
                category: 'savings',
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitSvWithdraw = (e) => {
        e.preventDefault();
        svWithdrawForm.post(
            route('finance.positions.movements.savings-withdraw', {
                category: 'savings',
                position: row.id,
            }),
            postOpts,
        );
    };

    const submitSvAdjust = (e) => {
        e.preventDefault();
        svAdjustForm.post(
            route('finance.positions.movements.savings-adjustment', {
                category: 'savings',
                position: row.id,
            }),
            postOpts,
        );
    };

    const printStatement = () => {
        const u = route('finance.positions.statement', {
            category,
            position: row.id,
            ...companyQuery,
        });
        window.open(u, '_blank', 'noopener');
    };

    const showLegacyLoanMenu =
        category === 'loan' && !isStructuredLoan && loanReady;
    const showStructuredReadyMenu =
        category === 'loan' && isStructuredLoan && loanReady;
    const showStructuredSavingsReadyMenu =
        category === 'savings' && isStructuredSavings && savingsReady;
    const showSavingsLegacyMenu =
        category === 'savings' && !isStructuredSavings;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <button
                type="button"
                className="absolute inset-0 bg-slate-900/50"
                aria-label="Close"
                onClick={onClose}
            />
            <div className="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
                <div className="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">
                            {row.title}
                        </h3>
                        {row.account_number && (
                            <p className="mt-0.5 font-mono text-xs tabular-nums text-gray-500">
                                Account {row.account_number}
                            </p>
                        )}
                        {isStructuredLoan &&
                            !loanReady &&
                            row.proposed_account_number && (
                                <p className="mt-1 text-xs font-medium text-amber-900">
                                    Proposed account{' '}
                                    <span className="font-mono tabular-nums">
                                        {row.proposed_account_number}
                                    </span>
                                    {' — '}
                                    awaiting back office approval before this
                                    account can be used.
                                </p>
                            )}
                        {isStructuredSavings &&
                            !savingsReady &&
                            row.proposed_account_number && (
                                <p className="mt-1 text-xs font-medium text-amber-900">
                                    Proposed account{' '}
                                    <span className="font-mono tabular-nums">
                                        {row.proposed_account_number}
                                    </span>
                                    {' — '}
                                    awaiting back office approval before this
                                    account can be used.
                                </p>
                            )}
                        <p className="text-sm text-gray-600">
                            {category === 'loan' ? 'Loan' : 'Savings'} product ·
                            Balance{' '}
                            <span className="font-semibold tabular-nums">
                                {moneyFromCents(row.principal_cents)}
                            </span>
                        </p>
                    </div>
                    <button
                        type="button"
                        className="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                        onClick={onClose}
                    >
                        ✕
                    </button>
                </div>

                {view === VIEWS.MENU && (
                    <div className="mt-4 grid gap-2">
                        {isStructuredLoan && !loanReady && (
                            <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                                Approve this application in the{' '}
                                <strong>back office</strong> (loan schedule
                                page) to activate the account number and enable
                                disbursements, repayments, and statements.
                            </p>
                        )}

                        {isStructuredSavings && !savingsReady && (
                            <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                                Approve this application in the{' '}
                                <strong>back office</strong> (savings schedule
                                page) to activate the account number and enable
                                deposits, withdrawals, and statements.
                            </p>
                        )}

                        {showStructuredReadyMenu && (
                            <>
                                <p className="text-xs text-gray-600">
                                    Each disbursement, installment, and penalty
                                    creates a matching{' '}
                                    <strong>journal entry</strong> (debit /
                                    credit you choose) so the general ledger
                                    stays in sync.
                                </p>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.DISBURSE)}
                                >
                                    Disburse loan
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.INSTALLMENT)}
                                >
                                    Pay installment (principal)
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.PENALTY)}
                                >
                                    Record penalty / late charge
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.STATEMENT)}
                                >
                                    View statement
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.ADJUST)}
                                >
                                    Adjustment (principal)
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-left text-sm font-medium text-indigo-900 hover:bg-indigo-100"
                                    onClick={printStatement}
                                >
                                    Print statement
                                </button>
                                <p className="text-xs text-gray-500">
                                    Monthly <strong>interest</strong> is
                                    accrued on the schedule page; post each
                                    month to the journal from there.
                                </p>
                            </>
                        )}

                        {showStructuredSavingsReadyMenu && (
                            <>
                                <p className="text-xs text-gray-600">
                                    Each deposit, withdrawal, and adjustment
                                    creates a matching{' '}
                                    <strong>journal entry</strong> (debit /
                                    credit you choose) so the general ledger
                                    stays in sync.
                                </p>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.DEPOSIT)}
                                >
                                    Deposit
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.WITHDRAW)}
                                >
                                    Withdraw
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.ADJUST)}
                                >
                                    Adjustment (balance)
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.STATEMENT)}
                                >
                                    View statement
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-left text-sm font-medium text-indigo-900 hover:bg-indigo-100"
                                    onClick={printStatement}
                                >
                                    Print statement
                                </button>
                                <p className="text-xs text-gray-500">
                                    Monthly <strong>interest</strong> is accrued
                                    on the schedule page; post savings interest
                                    quarterly to the journal from there.
                                </p>
                            </>
                        )}

                        {(showSavingsLegacyMenu || showLegacyLoanMenu) && (
                            <>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.DEPOSIT)}
                                >
                                    {category === 'loan'
                                        ? 'Add deposit'
                                        : 'Add deposit'}
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.WITHDRAW)}
                                >
                                    {category === 'loan'
                                        ? 'Withdraw / repay principal'
                                        : 'Withdraw'}
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.STATEMENT)}
                                >
                                    View statement
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-800 hover:bg-gray-50"
                                    onClick={() => setView(VIEWS.ADJUST)}
                                >
                                    Adjustment
                                </button>
                                <button
                                    type="button"
                                    className="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-left text-sm font-medium text-indigo-900 hover:bg-indigo-100"
                                    onClick={printStatement}
                                >
                                    Print statement
                                </button>
                            </>
                        )}
                    </div>
                )}

                {view === VIEWS.DISBURSE && (
                    <form onSubmit={submitDisburse} className="mt-4 space-y-4">
                        <button
                            type="button"
                            className="text-sm text-indigo-600"
                            onClick={() => setView(VIEWS.MENU)}
                        >
                            ← Back
                        </button>
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={
                                    disburseForm.data.company_id ||
                                    currentCompanyId
                                }
                            />
                        )}
                        <LedgerFields
                            accounts={chartAccounts}
                            data={disburseForm.data}
                            setData={disburseForm.setData}
                            errors={disburseForm.errors}
                        />
                        <div>
                            <InputLabel value="Amount (NPR)" />
                            <TextInput
                                type="number"
                                step="0.01"
                                min="0.01"
                                className="mt-1 block w-full"
                                value={disburseForm.data.amount}
                                onChange={(e) =>
                                    disburseForm.setData(
                                        'amount',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={disburseForm.errors.amount}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel value="Memo (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={disburseForm.data.memo}
                                onChange={(e) =>
                                    disburseForm.setData('memo', e.target.value)
                                }
                            />
                        </div>
                        <PrimaryButton disabled={disburseForm.processing}>
                            Save disbursement
                        </PrimaryButton>
                    </form>
                )}

                {view === VIEWS.INSTALLMENT && (
                    <form
                        onSubmit={submitInstallment}
                        className="mt-4 space-y-4"
                    >
                        <button
                            type="button"
                            className="text-sm text-indigo-600"
                            onClick={() => setView(VIEWS.MENU)}
                        >
                            ← Back
                        </button>
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={
                                    installmentForm.data.company_id ||
                                    currentCompanyId
                                }
                            />
                        )}
                        <LedgerFields
                            accounts={chartAccounts}
                            data={installmentForm.data}
                            setData={installmentForm.setData}
                            errors={installmentForm.errors}
                        />
                        <div>
                            <InputLabel value="Principal payment (NPR)" />
                            <TextInput
                                type="number"
                                step="0.01"
                                min="0.01"
                                className="mt-1 block w-full"
                                value={installmentForm.data.amount}
                                onChange={(e) =>
                                    installmentForm.setData(
                                        'amount',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={installmentForm.errors.amount}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel value="Memo (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={installmentForm.data.memo}
                                onChange={(e) =>
                                    installmentForm.setData(
                                        'memo',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <PrimaryButton disabled={installmentForm.processing}>
                            Save installment
                        </PrimaryButton>
                    </form>
                )}

                {view === VIEWS.PENALTY && (
                    <form onSubmit={submitPenalty} className="mt-4 space-y-4">
                        <button
                            type="button"
                            className="text-sm text-indigo-600"
                            onClick={() => setView(VIEWS.MENU)}
                        >
                            ← Back
                        </button>
                        {isAdmin && (
                            <input
                                type="hidden"
                                name="company_id"
                                value={
                                    penaltyForm.data.company_id ||
                                    currentCompanyId
                                }
                            />
                        )}
                        <LedgerFields
                            accounts={chartAccounts}
                            data={penaltyForm.data}
                            setData={penaltyForm.setData}
                            errors={penaltyForm.errors}
                        />
                        <div>
                            <InputLabel value="Penalty amount (NPR)" />
                            <TextInput
                                type="number"
                                step="0.01"
                                min="0.01"
                                className="mt-1 block w-full"
                                value={penaltyForm.data.amount}
                                onChange={(e) =>
                                    penaltyForm.setData(
                                        'amount',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            <InputError
                                message={penaltyForm.errors.amount}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel value="Memo (optional)" />
                            <TextInput
                                className="mt-1 block w-full"
                                value={penaltyForm.data.memo}
                                onChange={(e) =>
                                    penaltyForm.setData('memo', e.target.value)
                                }
                            />
                        </div>
                        <PrimaryButton disabled={penaltyForm.processing}>
                            Save penalty
                        </PrimaryButton>
                    </form>
                )}

                {view === VIEWS.DEPOSIT &&
                    isStructuredSavings &&
                    savingsReady && (
                        <form
                            onSubmit={submitSvDeposit}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        svDepositForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <LedgerFields
                                accounts={chartAccounts}
                                data={svDepositForm.data}
                                setData={svDepositForm.setData}
                                errors={svDepositForm.errors}
                            />
                            <div>
                                <InputLabel value="Amount (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    className="mt-1 block w-full"
                                    value={svDepositForm.data.amount}
                                    onChange={(e) =>
                                        svDepositForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={svDepositForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={svDepositForm.data.memo}
                                    onChange={(e) =>
                                        svDepositForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={svDepositForm.processing}>
                                Save deposit
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.DEPOSIT &&
                    !(isStructuredSavings && savingsReady) && (
                        <form
                            onSubmit={submitDeposit}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        depositForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <div>
                                <InputLabel value="Amount (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    className="mt-1 block w-full"
                                    value={depositForm.data.amount}
                                    onChange={(e) =>
                                        depositForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={depositForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={depositForm.data.memo}
                                    onChange={(e) =>
                                        depositForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={depositForm.processing}>
                                Save deposit
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.WITHDRAW &&
                    isStructuredSavings &&
                    savingsReady && (
                        <form
                            onSubmit={submitSvWithdraw}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        svWithdrawForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <LedgerFields
                                accounts={chartAccounts}
                                data={svWithdrawForm.data}
                                setData={svWithdrawForm.setData}
                                errors={svWithdrawForm.errors}
                            />
                            <div>
                                <InputLabel value="Amount (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    className="mt-1 block w-full"
                                    value={svWithdrawForm.data.amount}
                                    onChange={(e) =>
                                        svWithdrawForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={svWithdrawForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={svWithdrawForm.data.memo}
                                    onChange={(e) =>
                                        svWithdrawForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={svWithdrawForm.processing}>
                                Save withdrawal
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.WITHDRAW &&
                    !(isStructuredSavings && savingsReady) && (
                        <form
                            onSubmit={submitWithdraw}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        withdrawForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <div>
                                <InputLabel value="Amount (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    className="mt-1 block w-full"
                                    value={withdrawForm.data.amount}
                                    onChange={(e) =>
                                        withdrawForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={withdrawForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={withdrawForm.data.memo}
                                    onChange={(e) =>
                                        withdrawForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={withdrawForm.processing}>
                                Save withdrawal
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.ADJUST &&
                    isStructuredSavings &&
                    savingsReady && (
                        <form
                            onSubmit={submitSvAdjust}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            <p className="text-sm text-gray-600">
                                Enter a positive or negative dollar amount. A
                                journal entry is posted for the absolute change;
                                debit and credit follow the sign you intend
                                (swap accounts if you need the opposite
                                presentation).
                            </p>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        svAdjustForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <LedgerFields
                                accounts={chartAccounts}
                                data={svAdjustForm.data}
                                setData={svAdjustForm.setData}
                                errors={svAdjustForm.errors}
                            />
                            <div>
                                <InputLabel value="Adjustment (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    className="mt-1 block w-full"
                                    value={svAdjustForm.data.amount}
                                    onChange={(e) =>
                                        svAdjustForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={svAdjustForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={svAdjustForm.data.memo}
                                    onChange={(e) =>
                                        svAdjustForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={svAdjustForm.processing}>
                                Save adjustment
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.ADJUST &&
                    !(isStructuredSavings && savingsReady) && (
                        <form
                            onSubmit={submitAdjust}
                            className="mt-4 space-y-4"
                        >
                            <button
                                type="button"
                                className="text-sm text-indigo-600"
                                onClick={() => setView(VIEWS.MENU)}
                            >
                                ← Back
                            </button>
                            <p className="text-sm text-gray-600">
                                Enter a positive or negative dollar amount to
                                change the principal / balance.
                            </p>
                            {isAdmin && (
                                <input
                                    type="hidden"
                                    name="company_id"
                                    value={
                                        adjustForm.data.company_id ||
                                        currentCompanyId
                                    }
                                />
                            )}
                            <div>
                                <InputLabel value="Adjustment (NPR)" />
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    className="mt-1 block w-full"
                                    value={adjustForm.data.amount}
                                    onChange={(e) =>
                                        adjustForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={adjustForm.errors.amount}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Memo (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={adjustForm.data.memo}
                                    onChange={(e) =>
                                        adjustForm.setData(
                                            'memo',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <PrimaryButton disabled={adjustForm.processing}>
                                Save adjustment
                            </PrimaryButton>
                        </form>
                    )}

                {view === VIEWS.STATEMENT && (
                    <div className="mt-4">
                        <button
                            type="button"
                            className="text-sm text-indigo-600"
                            onClick={() => setView(VIEWS.MENU)}
                        >
                            ← Back
                        </button>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                className="rounded-md bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white"
                                onClick={() => loadStatement()}
                            >
                                Refresh
                            </button>
                            <button
                                type="button"
                                className="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-800"
                                onClick={printStatement}
                            >
                                Print
                            </button>
                        </div>
                        {loading && (
                            <p className="mt-3 text-sm text-gray-500">
                                Loading…
                            </p>
                        )}
                        {statementError && (
                            <p className="mt-3 text-sm text-red-600">
                                {statementError}
                            </p>
                        )}
                        {!loading && statementRows.length === 0 && (
                            <p className="mt-3 text-sm text-gray-600">
                                No movements yet.
                            </p>
                        )}
                        {statementRows.length > 0 && (
                            <div className="mt-3 max-h-64 overflow-auto rounded border border-gray-200">
                                <table className="min-w-full text-left text-xs">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-2 py-2">When</th>
                                            <th className="px-2 py-2">Type</th>
                                            <th className="px-2 py-2 text-right">
                                                Change
                                            </th>
                                            <th className="px-2 py-2 text-right">
                                                Balance
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {statementRows.map((r) => (
                                            <tr
                                                key={r.id}
                                                className="border-t border-gray-100"
                                            >
                                                <td className="px-2 py-1.5 whitespace-nowrap text-gray-700">
                                                    {r.created_at
                                                        ? formatDisplayDateTime(
                                                              r.created_at,
                                                          )
                                                        : '—'}
                                                </td>
                                                <td className="px-2 py-1.5">
                                                    {r.type_label}
                                                </td>
                                                <td className="px-2 py-1.5 text-right tabular-nums">
                                                    {moneyFromCents(
                                                        r.amount_cents,
                                                    )}
                                                </td>
                                                <td className="px-2 py-1.5 text-right font-medium tabular-nums">
                                                    {moneyFromCents(
                                                        r.balance_after_cents,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
