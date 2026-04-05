<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\FinancialPosition;
use App\Models\FinancialPositionMovement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MemberPortalController extends Controller
{
    use ResolvesAccountingCompany;

    public function home(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isEndUser(), 403);

        $company = $this->accountingCompany($request);
        $member = $user->linkedMember();

        $state = $user->memberPortalState();

        $loans = [];
        $savings = [];

        if ($state === 'ok' && $member !== null) {
            $positions = FinancialPosition::query()
                ->where('company_id', $company->id)
                ->where('member_id', $member->id)
                ->whereIn('category', [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS])
                ->orderBy('category')
                ->orderBy('title')
                ->get();

            foreach ($positions as $p) {
                $row = [
                    'id' => $p->id,
                    'category' => $p->category,
                    'title' => $p->title,
                    'account_number' => $p->account_number ?? $p->proposedCatalogAccountNumber(),
                    'principal_cents' => (int) $p->principal_cents,
                    'is_operational' => $p->category === FinancialPosition::CATEGORY_LOAN
                        ? $p->isLoanOperational()
                        : ($p->category === FinancialPosition::CATEGORY_SAVINGS
                            ? $p->isSavingsOperational()
                            : true),
                ];
                if ($p->category === FinancialPosition::CATEGORY_LOAN) {
                    $loans[] = $row;
                } else {
                    $savings[] = $row;
                }
            }
        }

        return Inertia::render('Portal/Home', [
            'payment_info' => $this->companyPaymentDetailsForPortal($company),
            'portal_state' => $state,
            'member' => $member ? [
                'id' => $member->id,
                'name' => $member->name,
                'member_number' => $member->member_number,
                'status' => $member->status,
                'email' => $member->email,
            ] : null,
            'company' => ['name' => $company->name],
            'loans' => $loans,
            'savings' => $savings,
        ]);
    }

    public function passbook(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isEndUser() && $user->canViewMemberFinancePortal(), 403);

        $company = $this->accountingCompany($request);
        $member = $user->linkedMember();
        abort_unless($member !== null, 404);

        $positionIds = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('member_id', $member->id)
            ->whereIn('category', [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS])
            ->pluck('id');

        $rows = FinancialPositionMovement::query()
            ->whereIn('financial_position_id', $positionIds)
            ->with(['financialPosition:id,title,category,account_number'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function (FinancialPositionMovement $m) {
                $fp = $m->financialPosition;

                return [
                    'id' => $m->id,
                    'at' => $m->created_at?->toDateTimeString(),
                    'category' => $fp?->category,
                    'product_title' => $fp?->title,
                    'account_number' => $fp ? ($fp->account_number ?? $fp->proposedCatalogAccountNumber()) : null,
                    'type_label' => $this->movementTypeLabel($m->type),
                    'amount_cents' => (int) $m->amount_cents,
                    'balance_after_cents' => (int) $m->balance_after_cents,
                    'memo' => $m->memo,
                ];
            });

        return Inertia::render('Portal/Passbook', [
            'entries' => $rows,
            'letterhead' => $this->companyLetterhead($company),
            'payment_info' => $this->companyPaymentDetailsForPortal($company),
        ]);
    }

    public function position(Request $request, string $category, int $position): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isEndUser() && $user->canViewMemberFinancePortal(), 403);

        $category = $this->validatedCategory($category);
        $record = $this->positionForMember($request, $user, $category, $position);

        $company = $this->accountingCompany($request);
        $qrPayload = $this->paymentQrPayload($company->id, $company->name, $category, $record);

        return Inertia::render('Portal/Position', [
            'payment_info' => $this->companyPaymentDetailsForPortal($company),
            'category' => $category,
            'category_label' => $category === FinancialPosition::CATEGORY_LOAN ? __('Loan') : __('Savings'),
            'position' => [
                'id' => $record->id,
                'title' => $record->title,
                'account_number' => $record->account_number ?? $record->proposedCatalogAccountNumber(),
                'principal_cents' => (int) $record->principal_cents,
                'is_operational' => $category === FinancialPosition::CATEGORY_LOAN
                    ? $record->isLoanOperational()
                    : $record->isSavingsOperational(),
            ],
            'qr_payload' => $qrPayload,
            'letterhead' => $this->companyLetterhead($company),
        ]);
    }

    public function statement(Request $request, string $category, int $position): Response|SymfonyResponse
    {
        $user = $request->user();
        abort_unless($user->isEndUser() && $user->canViewMemberFinancePortal(), 403);

        $category = $this->validatedCategory($category);
        $record = $this->positionForMember($request, $user, $category, $position);

        if ($record->usesStructuredCatalogWorkflow() && ! $record->isStructuredCatalogOperational()) {
            abort(403, __('Statement is available after this account is approved.'));
        }

        $company = $this->accountingCompany($request);
        $record->load(['member:id,name,member_number', 'movements' => fn ($q) => $q->orderBy('created_at')->orderBy('id')]);

        $movements = $record->movements->map(fn (FinancialPositionMovement $m) => [
            'type' => $m->type,
            'type_label' => $this->movementTypeLabel($m->type),
            'amount_cents' => (int) $m->amount_cents,
            'balance_after_cents' => (int) $m->balance_after_cents,
            'memo' => $m->memo,
            'at' => $m->created_at?->toDateTimeString(),
        ])->values()->all();

        return Inertia::render('Accounting/Finance/Statement', [
            'category' => $category,
            'categoryLabel' => $category === FinancialPosition::CATEGORY_LOAN ? __('Loan') : __('Savings'),
            'letterhead' => $this->companyLetterhead($company),
            'position' => [
                'id' => $record->id,
                'title' => $record->title,
                'account_number' => $record->account_number ?? $record->proposedCatalogAccountNumber(),
                'principal_cents' => (int) $record->principal_cents,
                'member' => $record->member ? [
                    'name' => $record->member->name,
                    'member_number' => $record->member->member_number,
                ] : null,
            ],
            'movements' => $movements,
            'companies' => [],
            'currentCompanyId' => $company->id,
        ]);
    }

    public function messages(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isEndUser(), 403);

        $company = $this->accountingCompany($request);

        $rows = \App\Models\PortalMessage::query()
            ->where('company_id', $company->id)
            ->where('end_user_id', $user->id)
            ->with('author:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (\App\Models\PortalMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'created_at' => $m->created_at?->toIso8601String(),
                'from_you' => (int) $m->author_user_id === (int) $user->id,
                'author_name' => $m->author?->name,
            ]);

        return Inertia::render('Portal/Messages', [
            'messages' => $rows,
            'company_name' => $company->name,
            'can_chat' => true,
        ]);
    }

    public function storeMessage(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isEndUser(), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $company = $this->accountingCompany($request);

        \App\Models\PortalMessage::query()->create([
            'company_id' => $company->id,
            'end_user_id' => $user->id,
            'author_user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', __('Message sent.'));
    }

    private function validatedCategory(string $category): string
    {
        if (! in_array($category, [FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS], true)) {
            abort(404);
        }

        return $category;
    }

    private function positionForMember(Request $request, User $user, string $category, int $position): FinancialPosition
    {
        $member = $user->linkedMember();
        abort_unless($member !== null, 404);

        $company = $this->accountingCompany($request);

        return FinancialPosition::query()
            ->where('company_id', $company->id)
            ->where('member_id', $member->id)
            ->where('category', $category)
            ->findOrFail($position);
    }

    private function paymentQrPayload(int $companyId, string $companyName, string $category, FinancialPosition $record): string
    {
        $parts = [
            'type' => $category === FinancialPosition::CATEGORY_LOAN ? 'LOAN_PAY' : 'SAVINGS_DEPOSIT',
            'company_id' => $companyId,
            'company' => $companyName,
            'account' => $record->account_number ?? $record->proposedCatalogAccountNumber() ?? '',
            'member_ref' => (string) $record->member_id,
        ];

        return json_encode($parts, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function movementTypeLabel(string $type): string
    {
        return match ($type) {
            FinancialPositionMovement::TYPE_OPENING => __('Opening balance'),
            FinancialPositionMovement::TYPE_DEPOSIT => __('Deposit'),
            FinancialPositionMovement::TYPE_WITHDRAWAL => __('Withdrawal'),
            FinancialPositionMovement::TYPE_ADJUSTMENT => __('Adjustment'),
            FinancialPositionMovement::TYPE_DISBURSEMENT => __('Loan disbursement'),
            FinancialPositionMovement::TYPE_INSTALLMENT => __('Installment / principal repayment'),
            FinancialPositionMovement::TYPE_PENALTY => __('Penalty / late charge'),
            default => $type,
        };
    }
}
