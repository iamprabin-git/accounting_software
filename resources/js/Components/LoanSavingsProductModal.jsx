import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { moneyFromCents } from '@/utils/money';
import { useForm, router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';

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

function findMemberAccountByNumber(chartAccounts, accountNumber) {
    const code = String(accountNumber ?? '').trim().toLowerCase();
    if (!code) return null;

    return (
        chartAccounts.find((a) =>
            String(a.label ?? '')
                .toLowerCase()
                .startsWith(`${code} —`),
        ) ?? null
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
        transaction_date: todayIsoDate(),
        debit_chart_account_id: '',
        credit_chart_account_id: '',
        reference: '',
    });
    const withdrawForm = useForm({
        amount: '',
        memo: '',
        company_id: isAdmin ? String(currentCompanyId ?? '') : '',
        transaction_date: todayIsoDate(),
        debit_chart_account_id: '',
        credit_chart_account_id: '',
        reference: '',
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
    const savingsMemberAccount = useMemo(
        () => findMemberAccountByNumber(chartAccounts, row?.account_number),
        [chartAccounts, row?.account_number],
    );
    const loanMemberAccount = useMemo(
        () => findMemberAccountByNumber(chartAccounts, row?.account_number),
        [chartAccounts, row?.account_number],
    );
    const cashBankAccounts = useMemo(() => {
        const withoutMember = chartAccounts.filter(
            (a) => String(a.id) !== String(savingsMemberAccount?.id ?? ''),
        );
        const onlyCashOrBank = withoutMember.filter((a) =>
            /cash|bank/i.test(String(a.label ?? '')),
        );

        return onlyCashOrBank.length > 0 ? onlyCashOrBank : withoutMember;
    }, [chartAccounts, savingsMemberAccount?.id]);
    const loanCashBankAccounts = useMemo(() => {
        const withoutMember = chartAccounts.filter(
            (a) => String(a.id) !== String(loanMemberAccount?.id ?? ''),
        );
        const onlyCashOrBank = withoutMember.filter((a) =>
            /cash|bank/i.test(String(a.label ?? '')),
        );

        return onlyCashOrBank.length > 0 ? onlyCashOrBank : withoutMember;
    }, [chartAccounts, loanMemberAccount?.id]);

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
            depositForm.setData('company_id', c);
            withdrawForm.setData('company_id', c);
            disburseForm.setData('company_id', c);
            installmentForm.setData('company_id', c);
            penaltyForm.setData('company_id', c);
            svDepositForm.setData('company_id', c);
            svWithdrawForm.setData('company_id', c);
            svAdjustForm.setData('company_id', c);
        }
    }, [currentCompanyId, isAdmin, open]);

    useEffect(() => {
        if (!isStructuredSavings || !savingsReady || !savingsMemberAccount) {
            return;
        }

        svDepositForm.setData('credit_chart_account_id', String(savingsMemberAccount.id));
        if (String(svDepositForm.data.debit_chart_account_id) === String(savingsMemberAccount.id)) {
            svDepositForm.setData('debit_chart_account_id', '');
        }

        svWithdrawForm.setData('debit_chart_account_id', String(savingsMemberAccount.id));
        if (String(svWithdrawForm.data.credit_chart_account_id) === String(savingsMemberAccount.id)) {
            svWithdrawForm.setData('credit_chart_account_id', '');
        }
    }, [isStructuredSavings, savingsReady, savingsMemberAccount?.id, open]);

    useEffect(() => {
        if (!isStructuredLoan || !loanReady || !loanMemberAccount) {
            return;
        }

        disburseForm.setData('debit_chart_account_id', String(loanMemberAccount.id));
        if (String(disburseForm.data.credit_chart_account_id) === String(loanMemberAccount.id)) {
            disburseForm.setData('credit_chart_account_id', '');
        }

        installmentForm.setData('credit_chart_account_id', String(loanMemberAccount.id));
        if (String(installmentForm.data.debit_chart_account_id) === String(loanMemberAccount.id)) {
            installmentForm.setData('debit_chart_account_id', '');
        }

        penaltyForm.setData('debit_chart_account_id', String(loanMemberAccount.id));
        if (String(penaltyForm.data.credit_chart_account_id) === String(loanMemberAccount.id)) {
            penaltyForm.setData('credit_chart_account_id', '');
        }
    }, [isStructuredLoan, loanReady, loanMemberAccount?.id, open]);

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
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel value="Transaction date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={disburseForm.data.transaction_date}
                                    onChange={(e) =>
                                        disburseForm.setData('transaction_date', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={disburseForm.errors.transaction_date}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Dr account (Member personal)" />
                                <input
                                    type="hidden"
                                    name="debit_chart_account_id"
                                    value={disburseForm.data.debit_chart_account_id}
                                />
                                <TextInput
                                    className="mt-1 block w-full bg-gray-100"
                                    value={
                                        loanMemberAccount?.label ||
                                        row?.account_number ||
                                        'Member personal account'
                                    }
                                    readOnly
                                />
                                <InputError
                                    message={disburseForm.errors.debit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Cr account (Cash / Bank)" />
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={disburseForm.data.credit_chart_account_id}
                                    onChange={(e) =>
                                        disburseForm.setData('credit_chart_account_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Select cash / bank...</option>
                                    {loanCashBankAccounts.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={disburseForm.errors.credit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <InputLabel value="Reference (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={disburseForm.data.reference}
                                    onChange={(e) =>
                                        disburseForm.setData('reference', e.target.value)
                                    }
                                />
                                <InputError
                                    message={disburseForm.errors.reference}
                                    className="mt-1"
                                />
                            </div>
                        </div>
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
                            <InputLabel value="Description (optional)" />
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
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel value="Transaction date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={installmentForm.data.transaction_date}
                                    onChange={(e) =>
                                        installmentForm.setData('transaction_date', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={installmentForm.errors.transaction_date}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Dr account (Cash / Bank)" />
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={installmentForm.data.debit_chart_account_id}
                                    onChange={(e) =>
                                        installmentForm.setData('debit_chart_account_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Select cash / bank...</option>
                                    {loanCashBankAccounts.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={installmentForm.errors.debit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Cr account (Member personal)" />
                                <input
                                    type="hidden"
                                    name="credit_chart_account_id"
                                    value={installmentForm.data.credit_chart_account_id}
                                />
                                <TextInput
                                    className="mt-1 block w-full bg-gray-100"
                                    value={
                                        loanMemberAccount?.label ||
                                        row?.account_number ||
                                        'Member personal account'
                                    }
                                    readOnly
                                />
                                <InputError
                                    message={installmentForm.errors.credit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <InputLabel value="Reference (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={installmentForm.data.reference}
                                    onChange={(e) =>
                                        installmentForm.setData('reference', e.target.value)
                                    }
                                />
                                <InputError
                                    message={installmentForm.errors.reference}
                                    className="mt-1"
                                />
                            </div>
                        </div>
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
                            <InputLabel value="Description (optional)" />
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
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel value="Transaction date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={penaltyForm.data.transaction_date}
                                    onChange={(e) =>
                                        penaltyForm.setData('transaction_date', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={penaltyForm.errors.transaction_date}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Dr account (Member personal)" />
                                <input
                                    type="hidden"
                                    name="debit_chart_account_id"
                                    value={penaltyForm.data.debit_chart_account_id}
                                />
                                <TextInput
                                    className="mt-1 block w-full bg-gray-100"
                                    value={
                                        loanMemberAccount?.label ||
                                        row?.account_number ||
                                        'Member personal account'
                                    }
                                    readOnly
                                />
                                <InputError
                                    message={penaltyForm.errors.debit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <InputLabel value="Cr account (Income / Cash / Bank)" />
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={penaltyForm.data.credit_chart_account_id}
                                    onChange={(e) =>
                                        penaltyForm.setData('credit_chart_account_id', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Select account...</option>
                                    {chartAccounts
                                        .filter(
                                            (a) =>
                                                String(a.id) !==
                                                String(loanMemberAccount?.id ?? ''),
                                        )
                                        .map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.label}
                                            </option>
                                        ))}
                                </select>
                                <InputError
                                    message={penaltyForm.errors.credit_chart_account_id}
                                    className="mt-1"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <InputLabel value="Reference (optional)" />
                                <TextInput
                                    className="mt-1 block w-full"
                                    value={penaltyForm.data.reference}
                                    onChange={(e) =>
                                        penaltyForm.setData('reference', e.target.value)
                                    }
                                />
                                <InputError
                                    message={penaltyForm.errors.reference}
                                    className="mt-1"
                                />
                            </div>
                        </div>
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
                            <InputLabel value="Description (optional)" />
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
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <InputLabel value="Transaction date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={svDepositForm.data.transaction_date}
                                        onChange={(e) =>
                                            svDepositForm.setData('transaction_date', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={svDepositForm.errors.transaction_date}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Dr account (Cash / Bank)" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={svDepositForm.data.debit_chart_account_id}
                                        onChange={(e) =>
                                            svDepositForm.setData('debit_chart_account_id', e.target.value)
                                        }
                                        required
                                    >
                                        <option value="">Select cash / bank...</option>
                                        {cashBankAccounts.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={svDepositForm.errors.debit_chart_account_id}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Cr account (Member personal)" />
                                    <input
                                        type="hidden"
                                        name="credit_chart_account_id"
                                        value={svDepositForm.data.credit_chart_account_id}
                                    />
                                    <TextInput
                                        className="mt-1 block w-full bg-gray-100"
                                        value={
                                            savingsMemberAccount?.label ||
                                            row?.account_number ||
                                            'Member personal account'
                                        }
                                        readOnly
                                    />
                                    <InputError
                                        message={svDepositForm.errors.credit_chart_account_id}
                                        className="mt-1"
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel value="Reference (optional)" />
                                    <TextInput
                                        className="mt-1 block w-full"
                                        value={svDepositForm.data.reference}
                                        onChange={(e) =>
                                            svDepositForm.setData('reference', e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={svDepositForm.errors.reference}
                                        className="mt-1"
                                    />
                                </div>
                            </div>
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
                                <InputLabel value="Description (optional)" />
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
                            {(category === 'savings' ||
                                category === 'loan') && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <InputLabel value="Transaction date" />
                                        <TextInput
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={
                                                depositForm.data.transaction_date
                                            }
                                            onChange={(e) =>
                                                depositForm.setData(
                                                    'transaction_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                depositForm.errors
                                                    .transaction_date
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            value={
                                                category === 'savings'
                                                    ? 'Dr account (Cash / Bank)'
                                                    : 'Dr account (Member loan)'
                                            }
                                        />
                                        {category === 'savings' ? (
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    depositForm.data
                                                        .debit_chart_account_id
                                                }
                                                onChange={(e) =>
                                                    depositForm.setData(
                                                        'debit_chart_account_id',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="">
                                                    Select cash / bank…
                                                </option>
                                                {cashBankAccounts.map((a) => (
                                                    <option
                                                        key={a.id}
                                                        value={a.id}
                                                    >
                                                        {a.label}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <TextInput
                                                className="mt-1 block w-full bg-gray-100"
                                                value={
                                                    loanMemberAccount?.label ||
                                                    row?.account_number ||
                                                    'Member loan account (created on post if needed)'
                                                }
                                                readOnly
                                            />
                                        )}
                                        <InputError
                                            message={
                                                depositForm.errors
                                                    .debit_chart_account_id
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            value={
                                                category === 'savings'
                                                    ? 'Cr account (Member personal)'
                                                    : 'Cr account (Cash / Bank)'
                                            }
                                        />
                                        {category === 'savings' ? (
                                            <TextInput
                                                className="mt-1 block w-full bg-gray-100"
                                                value={
                                                    savingsMemberAccount?.label ||
                                                    row?.account_number ||
                                                    'Member savings liability (created on post if needed)'
                                                }
                                                readOnly
                                            />
                                        ) : (
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    depositForm.data
                                                        .credit_chart_account_id
                                                }
                                                onChange={(e) =>
                                                    depositForm.setData(
                                                        'credit_chart_account_id',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="">
                                                    Select cash / bank…
                                                </option>
                                                {loanCashBankAccounts.map(
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
                                        )}
                                        <InputError
                                            message={
                                                depositForm.errors
                                                    .credit_chart_account_id
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <InputLabel value="Reference (optional)" />
                                        <TextInput
                                            className="mt-1 block w-full"
                                            value={depositForm.data.reference}
                                            onChange={(e) =>
                                                depositForm.setData(
                                                    'reference',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                depositForm.errors.reference
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>
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
                            {category === 'savings' ? (
                                <p className="text-xs text-gray-600">
                                    Posts to the general ledger: debit cash/bank,
                                    credit member savings liability (same as
                                    product-based savings).
                                </p>
                            ) : category === 'loan' ? (
                                <p className="text-xs text-gray-600">
                                    Posts to the general ledger: debit member
                                    loan receivable, credit cash/bank
                                    (disbursement).
                                </p>
                            ) : null}
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
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <InputLabel value="Transaction date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={svWithdrawForm.data.transaction_date}
                                        onChange={(e) =>
                                            svWithdrawForm.setData('transaction_date', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={svWithdrawForm.errors.transaction_date}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Dr account (Member personal)" />
                                    <input
                                        type="hidden"
                                        name="debit_chart_account_id"
                                        value={svWithdrawForm.data.debit_chart_account_id}
                                    />
                                    <TextInput
                                        className="mt-1 block w-full bg-gray-100"
                                        value={
                                            savingsMemberAccount?.label ||
                                            row?.account_number ||
                                            'Member personal account'
                                        }
                                        readOnly
                                    />
                                    <InputError
                                        message={svWithdrawForm.errors.debit_chart_account_id}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Cr account (Cash / Bank)" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={svWithdrawForm.data.credit_chart_account_id}
                                        onChange={(e) =>
                                            svWithdrawForm.setData('credit_chart_account_id', e.target.value)
                                        }
                                        required
                                    >
                                        <option value="">Select cash / bank...</option>
                                        {cashBankAccounts.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={svWithdrawForm.errors.credit_chart_account_id}
                                        className="mt-1"
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel value="Reference (optional)" />
                                    <TextInput
                                        className="mt-1 block w-full"
                                        value={svWithdrawForm.data.reference}
                                        onChange={(e) =>
                                            svWithdrawForm.setData('reference', e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={svWithdrawForm.errors.reference}
                                        className="mt-1"
                                    />
                                </div>
                            </div>
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
                                <InputLabel value="Description (optional)" />
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
                            {(category === 'savings' ||
                                category === 'loan') && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <InputLabel value="Transaction date" />
                                        <TextInput
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={
                                                withdrawForm.data
                                                    .transaction_date
                                            }
                                            onChange={(e) =>
                                                withdrawForm.setData(
                                                    'transaction_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                withdrawForm.errors
                                                    .transaction_date
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            value={
                                                category === 'savings'
                                                    ? 'Dr account (Member personal)'
                                                    : 'Dr account (Cash / Bank)'
                                            }
                                        />
                                        {category === 'savings' ? (
                                            <TextInput
                                                className="mt-1 block w-full bg-gray-100"
                                                value={
                                                    savingsMemberAccount?.label ||
                                                    row?.account_number ||
                                                    'Member savings liability (created on post if needed)'
                                                }
                                                readOnly
                                            />
                                        ) : (
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    withdrawForm.data
                                                        .debit_chart_account_id
                                                }
                                                onChange={(e) =>
                                                    withdrawForm.setData(
                                                        'debit_chart_account_id',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="">
                                                    Select cash / bank…
                                                </option>
                                                {loanCashBankAccounts.map(
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
                                        )}
                                        <InputError
                                            message={
                                                withdrawForm.errors
                                                    .debit_chart_account_id
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            value={
                                                category === 'savings'
                                                    ? 'Cr account (Cash / Bank)'
                                                    : 'Cr account (Member loan)'
                                            }
                                        />
                                        {category === 'savings' ? (
                                            <select
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={
                                                    withdrawForm.data
                                                        .credit_chart_account_id
                                                }
                                                onChange={(e) =>
                                                    withdrawForm.setData(
                                                        'credit_chart_account_id',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="">
                                                    Select cash / bank…
                                                </option>
                                                {cashBankAccounts.map((a) => (
                                                    <option
                                                        key={a.id}
                                                        value={a.id}
                                                    >
                                                        {a.label}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <TextInput
                                                className="mt-1 block w-full bg-gray-100"
                                                value={
                                                    loanMemberAccount?.label ||
                                                    row?.account_number ||
                                                    'Member loan account (created on post if needed)'
                                                }
                                                readOnly
                                            />
                                        )}
                                        <InputError
                                            message={
                                                withdrawForm.errors
                                                    .credit_chart_account_id
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <InputLabel value="Reference (optional)" />
                                        <TextInput
                                            className="mt-1 block w-full"
                                            value={withdrawForm.data.reference}
                                            onChange={(e) =>
                                                withdrawForm.setData(
                                                    'reference',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                withdrawForm.errors.reference
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>
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
                            {category === 'savings' ? (
                                <p className="text-xs text-gray-600">
                                    Posts to the general ledger: debit member
                                    savings liability, credit cash/bank.
                                </p>
                            ) : category === 'loan' ? (
                                <p className="text-xs text-gray-600">
                                    Posts to the general ledger: debit cash/bank,
                                    credit member loan receivable (principal
                                    repayment).
                                </p>
                            ) : null}
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
                                Enter a positive or negative NPR amount. A
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
                                Enter a positive or negative NPR amount to
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
