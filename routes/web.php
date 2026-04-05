<?php

use App\Http\Controllers\Accounting\AccountingReportController;
use App\Http\Controllers\Accounting\ChartAccountApprovalController;
use App\Http\Controllers\Accounting\ChartAccountController;
use App\Http\Controllers\Accounting\CreditorController;
use App\Http\Controllers\Accounting\DebtorController;
use App\Http\Controllers\Accounting\FinancialPositionController;
use App\Http\Controllers\Accounting\LoanProductController;
use App\Http\Controllers\Accounting\SavingsProductController;
use App\Http\Controllers\Accounting\InventoryItemController;
use App\Http\Controllers\Accounting\JournalApprovalController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\MemberController;
use App\Http\Controllers\Accounting\MemberLedgerController;
use App\Http\Controllers\Accounting\WorkspaceController;
use App\Http\Controllers\Crm\CrmAccountController;
use App\Http\Controllers\Crm\CrmActivityController;
use App\Http\Controllers\Crm\CrmContactController;
use App\Http\Controllers\Crm\CrmDashboardController;
use App\Http\Controllers\Crm\CrmOpportunityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Company\CompanyProfileController;
use App\Http\Controllers\Company\CustomerMessageController;
use App\Http\Controllers\Company\TeamUserController;
use App\Http\Controllers\Portal\MemberPortalController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'googleAuthEnabled' => AuthenticatedSessionController::googleAuthConfigured(),
        'contactSuccess' => session('contactSuccess'),
    ]);
})->name('home');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'customer.active'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'customer.active', 'role:end_user', 'company.feature:members'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [MemberPortalController::class, 'home'])->name('home');
    Route::get('/passbook', [MemberPortalController::class, 'passbook'])->name('passbook');
    Route::get('/messages', [MemberPortalController::class, 'messages'])->name('messages');
    Route::post('/messages', [MemberPortalController::class, 'storeMessage'])->name('messages.store');
    Route::get('/{category}/{position}/statement', [MemberPortalController::class, 'statement'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('positions.statement');
    Route::get('/{category}/{position}', [MemberPortalController::class, 'position'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('positions.show');
});

Route::middleware(['auth', 'verified', 'customer.active', 'role:admin,company,staff'])->group(function () {
    Route::middleware('company.feature:finance')->group(function () {
        Route::get('/workspace/front-desk', [WorkspaceController::class, 'frontDesk'])->name('workspace.front-desk');
        Route::get('/workspace/back-office', [WorkspaceController::class, 'backOffice'])->name('workspace.back-office');
    });

    Route::get('/chart-of-accounts', [ChartAccountController::class, 'index'])->name('chart-accounts.index');
    Route::get('/chart-of-accounts/catalog', [ChartAccountController::class, 'catalog'])->name('chart-accounts.catalog');
    Route::post('/chart-of-accounts/from-template', [ChartAccountController::class, 'storeFromTemplate'])->name('chart-accounts.from-template');
    Route::get('/chart-of-accounts/create', [ChartAccountController::class, 'create'])->name('chart-accounts.create');
    Route::post('/chart-of-accounts', [ChartAccountController::class, 'store'])->name('chart-accounts.store');
    Route::get('/chart-of-accounts/{account}/edit', [ChartAccountController::class, 'edit'])->whereNumber('account')->name('chart-accounts.edit');
    Route::put('/chart-of-accounts/{account}', [ChartAccountController::class, 'update'])->whereNumber('account')->name('chart-accounts.update');
    Route::delete('/chart-of-accounts/{account}', [ChartAccountController::class, 'destroy'])->whereNumber('account')->name('chart-accounts.destroy');
    Route::post('/chart-of-accounts/{account}/approve', [ChartAccountApprovalController::class, 'approve'])->whereNumber('account')->name('chart-accounts.approve');
    Route::post('/chart-of-accounts/{account}/reject', [ChartAccountApprovalController::class, 'reject'])->whereNumber('account')->name('chart-accounts.reject');

    Route::middleware('company.feature:crm')->prefix('crm')->name('crm.')->group(function () {
        Route::get('/', [CrmDashboardController::class, 'index'])->name('dashboard');
        Route::get('/accounts', [CrmAccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [CrmAccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [CrmAccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}', [CrmAccountController::class, 'show'])->whereNumber('account')->name('accounts.show');
        Route::get('/accounts/{account}/edit', [CrmAccountController::class, 'edit'])->whereNumber('account')->name('accounts.edit');
        Route::put('/accounts/{account}', [CrmAccountController::class, 'update'])->whereNumber('account')->name('accounts.update');
        Route::delete('/accounts/{account}', [CrmAccountController::class, 'destroy'])->whereNumber('account')->name('accounts.destroy');
        Route::get('/contacts', [CrmContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/create', [CrmContactController::class, 'create'])->name('contacts.create');
        Route::post('/contacts', [CrmContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/{contact}/edit', [CrmContactController::class, 'edit'])->whereNumber('contact')->name('contacts.edit');
        Route::put('/contacts/{contact}', [CrmContactController::class, 'update'])->whereNumber('contact')->name('contacts.update');
        Route::delete('/contacts/{contact}', [CrmContactController::class, 'destroy'])->whereNumber('contact')->name('contacts.destroy');
        Route::get('/opportunities', [CrmOpportunityController::class, 'index'])->name('opportunities.index');
        Route::get('/opportunities/create', [CrmOpportunityController::class, 'create'])->name('opportunities.create');
        Route::post('/opportunities', [CrmOpportunityController::class, 'store'])->name('opportunities.store');
        Route::get('/opportunities/{opportunity}/edit', [CrmOpportunityController::class, 'edit'])->whereNumber('opportunity')->name('opportunities.edit');
        Route::put('/opportunities/{opportunity}', [CrmOpportunityController::class, 'update'])->whereNumber('opportunity')->name('opportunities.update');
        Route::delete('/opportunities/{opportunity}', [CrmOpportunityController::class, 'destroy'])->whereNumber('opportunity')->name('opportunities.destroy');
        Route::get('/activities', [CrmActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [CrmActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities', [CrmActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}/edit', [CrmActivityController::class, 'edit'])->whereNumber('activity')->name('activities.edit');
        Route::put('/activities/{activity}', [CrmActivityController::class, 'update'])->whereNumber('activity')->name('activities.update');
        Route::post('/activities/{activity}/complete', [CrmActivityController::class, 'complete'])->whereNumber('activity')->name('activities.complete');
        Route::delete('/activities/{activity}', [CrmActivityController::class, 'destroy'])->whereNumber('activity')->name('activities.destroy');
    });

    Route::middleware('company.feature:inventory')->group(function () {
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryItemController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}', [InventoryItemController::class, 'show'])->whereNumber('item')->name('inventory.show');
    Route::post('/inventory/{item}/purchase', [InventoryItemController::class, 'recordPurchase'])
        ->whereNumber('item')
        ->name('inventory.purchase');
    Route::post('/inventory/{item}/sale', [InventoryItemController::class, 'recordSale'])
        ->whereNumber('item')
        ->name('inventory.sale');
    Route::get('/inventory/{item}/edit', [InventoryItemController::class, 'edit'])->whereNumber('item')->name('inventory.edit');
    Route::put('/inventory/{item}', [InventoryItemController::class, 'update'])->whereNumber('item')->name('inventory.update');
    Route::delete('/inventory/{item}', [InventoryItemController::class, 'destroy'])->whereNumber('item')->name('inventory.destroy');
    });

    Route::middleware('company.feature:debtors_creditors')->group(function () {
    Route::get('/debtors', [DebtorController::class, 'index'])->name('debtors.index');
    Route::get('/debtors/create', [DebtorController::class, 'create'])->name('debtors.create');
    Route::post('/debtors', [DebtorController::class, 'store'])->name('debtors.store');
    Route::get('/debtors/{debtor}/edit', [DebtorController::class, 'edit'])->whereNumber('debtor')->name('debtors.edit');
    Route::put('/debtors/{debtor}', [DebtorController::class, 'update'])->whereNumber('debtor')->name('debtors.update');
    Route::delete('/debtors/{debtor}', [DebtorController::class, 'destroy'])->whereNumber('debtor')->name('debtors.destroy');

    Route::get('/creditors', [CreditorController::class, 'index'])->name('creditors.index');
    Route::get('/creditors/create', [CreditorController::class, 'create'])->name('creditors.create');
    Route::post('/creditors', [CreditorController::class, 'store'])->name('creditors.store');
    Route::get('/creditors/{creditor}/edit', [CreditorController::class, 'edit'])->whereNumber('creditor')->name('creditors.edit');
    Route::put('/creditors/{creditor}', [CreditorController::class, 'update'])->whereNumber('creditor')->name('creditors.update');
    Route::delete('/creditors/{creditor}', [CreditorController::class, 'destroy'])->whereNumber('creditor')->name('creditors.destroy');
    });

    Route::middleware('company.feature:members')->group(function () {
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member}/ledger', [MemberLedgerController::class, 'show'])->whereNumber('member')->name('members.ledger');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->whereNumber('member')->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])->whereNumber('member')->name('members.update');
    Route::post('/members/{member}/approve', [MemberController::class, 'approve'])->whereNumber('member')->name('members.approve');
    Route::post('/members/{member}/reject', [MemberController::class, 'reject'])->whereNumber('member')->name('members.reject');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->whereNumber('member')->name('members.destroy');
    });

    Route::middleware('company.feature:finance')->group(function () {
    Route::get('/finance/loan-products', [LoanProductController::class, 'index'])->name('finance.loan-products.index');
    Route::get('/finance/loan-products/create', [LoanProductController::class, 'create'])->name('finance.loan-products.create');
    Route::post('/finance/loan-products', [LoanProductController::class, 'store'])->name('finance.loan-products.store');
    Route::get('/finance/loan-products/{loanProduct}/edit', [LoanProductController::class, 'edit'])->whereNumber('loanProduct')->name('finance.loan-products.edit');
    Route::put('/finance/loan-products/{loanProduct}', [LoanProductController::class, 'update'])->whereNumber('loanProduct')->name('finance.loan-products.update');
    Route::delete('/finance/loan-products/{loanProduct}', [LoanProductController::class, 'destroy'])->whereNumber('loanProduct')->name('finance.loan-products.destroy');

    Route::get('/finance/savings-products', [SavingsProductController::class, 'index'])->name('finance.savings-products.index');
    Route::get('/finance/savings-products/create', [SavingsProductController::class, 'create'])->name('finance.savings-products.create');
    Route::post('/finance/savings-products', [SavingsProductController::class, 'store'])->name('finance.savings-products.store');
    Route::get('/finance/savings-products/{savingsProduct}/edit', [SavingsProductController::class, 'edit'])->whereNumber('savingsProduct')->name('finance.savings-products.edit');
    Route::put('/finance/savings-products/{savingsProduct}', [SavingsProductController::class, 'update'])->whereNumber('savingsProduct')->name('finance.savings-products.update');
    Route::delete('/finance/savings-products/{savingsProduct}', [SavingsProductController::class, 'destroy'])->whereNumber('savingsProduct')->name('finance.savings-products.destroy');

    Route::get('/finance/account-entry', [FinancialPositionController::class, 'accountEntry'])
        ->name('finance.account-entry');
    Route::get('/finance/{category}', [FinancialPositionController::class, 'index'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->name('finance.positions.index');
    Route::get('/finance/{category}/create', [FinancialPositionController::class, 'create'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->name('finance.positions.create');
    Route::post('/finance/{category}', [FinancialPositionController::class, 'store'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->name('finance.positions.store');
    Route::get('/finance/{category}/{position}', [FinancialPositionController::class, 'show'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.show');
    Route::get('/finance/{category}/{position}/statement', [FinancialPositionController::class, 'statement'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.statement');
    Route::get('/finance/{category}/{position}/movements-data', [FinancialPositionController::class, 'movementsData'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.movements-data');
    Route::post('/finance/{category}/{position}/movements/deposit', [FinancialPositionController::class, 'storeDeposit'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.movements.deposit');
    Route::post('/finance/{category}/{position}/movements/withdraw', [FinancialPositionController::class, 'storeWithdrawal'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.movements.withdraw');
    Route::post('/finance/{category}/{position}/movements/adjustment', [FinancialPositionController::class, 'storeAdjustment'])
        ->whereIn('category', ['loan', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.movements.adjustment');
    Route::post('/finance/{category}/{position}/movements/disburse', [FinancialPositionController::class, 'storeDisbursement'])
        ->where('category', 'loan')
        ->whereNumber('position')
        ->name('finance.positions.movements.disburse');
    Route::post('/finance/{category}/{position}/movements/installment', [FinancialPositionController::class, 'storeInstallment'])
        ->where('category', 'loan')
        ->whereNumber('position')
        ->name('finance.positions.movements.installment');
    Route::post('/finance/{category}/{position}/movements/penalty', [FinancialPositionController::class, 'storePenalty'])
        ->where('category', 'loan')
        ->whereNumber('position')
        ->name('finance.positions.movements.penalty');
    Route::post('/finance/{category}/{position}/loan/approve', [FinancialPositionController::class, 'approveLoanApplication'])
        ->where('category', 'loan')
        ->whereNumber('position')
        ->name('finance.positions.loan.approve');
    Route::post('/finance/{category}/{position}/loan/reject', [FinancialPositionController::class, 'rejectLoanApplication'])
        ->where('category', 'loan')
        ->whereNumber('position')
        ->name('finance.positions.loan.reject');
    Route::post('/finance/{category}/{position}/savings/approve', [FinancialPositionController::class, 'approveSavingsApplication'])
        ->where('category', 'savings')
        ->whereNumber('position')
        ->name('finance.positions.savings.approve');
    Route::post('/finance/{category}/{position}/savings/reject', [FinancialPositionController::class, 'rejectSavingsApplication'])
        ->where('category', 'savings')
        ->whereNumber('position')
        ->name('finance.positions.savings.reject');
    Route::post('/finance/{category}/{position}/movements/savings-deposit', [FinancialPositionController::class, 'storeStructuredSavingsDeposit'])
        ->where('category', 'savings')
        ->whereNumber('position')
        ->name('finance.positions.movements.savings-deposit');
    Route::post('/finance/{category}/{position}/movements/savings-withdraw', [FinancialPositionController::class, 'storeStructuredSavingsWithdrawal'])
        ->where('category', 'savings')
        ->whereNumber('position')
        ->name('finance.positions.movements.savings-withdraw');
    Route::post('/finance/{category}/{position}/movements/savings-adjustment', [FinancialPositionController::class, 'storeStructuredSavingsAdjustment'])
        ->where('category', 'savings')
        ->whereNumber('position')
        ->name('finance.positions.movements.savings-adjustment');
    Route::post('/finance/{category}/{position}/accruals/sync-year', [FinancialPositionController::class, 'syncAccrualYear'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.accruals.sync-year');
    Route::post('/finance/{category}/{position}/accruals/manual', [FinancialPositionController::class, 'storeManualAccrual'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.accruals.manual');
    Route::post('/finance/{category}/{position}/accruals/{accrual}/post-ledger', [FinancialPositionController::class, 'postAccrualToLedger'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->whereNumber('accrual')
        ->name('finance.positions.accruals.post-ledger');
    Route::post('/finance/{category}/{position}/accruals/post-savings-quarter', [FinancialPositionController::class, 'postSavingsQuarterToLedger'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.accruals.post-savings-quarter');
    Route::get('/finance/{category}/{position}/edit', [FinancialPositionController::class, 'edit'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.edit');
    Route::put('/finance/{category}/{position}', [FinancialPositionController::class, 'update'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.update');
    Route::delete('/finance/{category}/{position}', [FinancialPositionController::class, 'destroy'])
        ->whereIn('category', ['loan', 'investment', 'savings'])
        ->whereNumber('position')
        ->name('finance.positions.destroy');
    });

    Route::get('/journals', [JournalEntryController::class, 'index'])->name('journals.index');
    Route::get('/journals/create', [JournalEntryController::class, 'create'])->name('journals.create');
    Route::get('/journals/cash-in/create', [JournalEntryController::class, 'createCashIn'])->name('journals.create-cash-in');
    Route::get('/journals/cash-out/create', [JournalEntryController::class, 'createCashOut'])->name('journals.create-cash-out');
    Route::post('/journals', [JournalEntryController::class, 'store'])->name('journals.store');
    Route::post('/journals/cash-in', [JournalEntryController::class, 'storeCashIn'])->name('journals.store-cash-in');
    Route::post('/journals/cash-out', [JournalEntryController::class, 'storeCashOut'])->name('journals.store-cash-out');
    Route::get('/journals/{journal}', [JournalEntryController::class, 'show'])->whereNumber('journal')->name('journals.show');
    Route::get('/journals/{journal}/edit', [JournalEntryController::class, 'edit'])->whereNumber('journal')->name('journals.edit');
    Route::put('/journals/{journal}', [JournalEntryController::class, 'update'])->whereNumber('journal')->name('journals.update');
    Route::delete('/journals/{journal}', [JournalEntryController::class, 'destroy'])->whereNumber('journal')->name('journals.destroy');
    Route::post('/journals/{journal}/submit', [JournalEntryController::class, 'submit'])->whereNumber('journal')->name('journals.submit');
    Route::post('/journals/{journal}/approve', [JournalApprovalController::class, 'approve'])->whereNumber('journal')->name('journals.approve');
    Route::post('/journals/{journal}/reject', [JournalApprovalController::class, 'reject'])->whereNumber('journal')->name('journals.reject');

    Route::get('/reports', [AccountingReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/reports/profit-loss', [AccountingReportController::class, 'profitAndLoss'])->name('reports.profit-loss');
    Route::get('/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/reports/cash-flow', [AccountingReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');
});

Route::middleware(['auth', 'verified', 'customer.active', 'role:company,staff', 'company.feature:members'])->prefix('company/customer-chat')->name('company.customer-chat.')->group(function () {
    Route::get('/', [CustomerMessageController::class, 'index'])->name('index');
    Route::get('/{endUser}', [CustomerMessageController::class, 'show'])->whereNumber('endUser')->name('show');
    Route::post('/{endUser}', [CustomerMessageController::class, 'store'])->whereNumber('endUser')->name('store');
});

Route::middleware(['auth', 'verified', 'customer.active', 'role:company'])->group(function () {
    Route::get('/company/profile', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::post('/company/profile', [CompanyProfileController::class, 'update'])->name('company.profile.update');
});

Route::middleware(['auth', 'verified', 'customer.active', 'role:company'])->prefix('company/team')->name('company.team.')->group(function () {
    Route::get('/', [TeamUserController::class, 'index'])->name('index');
    Route::get('/create', [TeamUserController::class, 'create'])->name('create');
    Route::post('/', [TeamUserController::class, 'store'])->name('store');
    Route::post('/{member}/activate', [TeamUserController::class, 'activate'])->name('activate');
    Route::post('/{member}/approve-portal', [TeamUserController::class, 'approvePortal'])->name('approve-portal');
    Route::post('/{member}/revoke-portal', [TeamUserController::class, 'revokePortal'])->name('revoke-portal');
    Route::get('/{member}/edit', [TeamUserController::class, 'edit'])->name('edit');
    Route::put('/{member}', [TeamUserController::class, 'update'])->name('update');
    Route::delete('/{member}', [TeamUserController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'customer.active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
