<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\PortalMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeCompanyStaff($request);

        $companyId = (int) $request->user()->company_id;

        $endUserIds = PortalMessage::query()
            ->where('company_id', $companyId)
            ->distinct()
            ->pluck('end_user_id');

        $payload = [];

        foreach ($endUserIds as $uid) {
            $endUser = User::query()
                ->where('company_id', $companyId)
                ->where('id', $uid)
                ->where('role', User::ROLE_END_USER)
                ->first(['id', 'name', 'email']);

            if ($endUser === null) {
                continue;
            }

            $last = PortalMessage::query()
                ->where('company_id', $companyId)
                ->where('end_user_id', $endUser->id)
                ->latest('created_at')
                ->first();

            $payload[] = [
                'end_user_id' => $endUser->id,
                'name' => $endUser->name,
                'email' => $endUser->email,
                'last_body' => $last ? mb_substr($last->body, 0, 120) : '',
                'last_at' => $last?->created_at?->toIso8601String(),
            ];
        }

        usort($payload, fn ($a, $b) => strcmp((string) ($b['last_at'] ?? ''), (string) ($a['last_at'] ?? '')));

        return Inertia::render('Company/CustomerChat/Index', [
            'threads' => $payload,
        ]);
    }

    public function show(Request $request, int $endUser): Response
    {
        $this->authorizeCompanyStaff($request);

        $companyId = (int) $request->user()->company_id;

        $customer = User::query()
            ->where('company_id', $companyId)
            ->where('role', User::ROLE_END_USER)
            ->findOrFail($endUser);

        $messages = PortalMessage::query()
            ->where('company_id', $companyId)
            ->where('end_user_id', $customer->id)
            ->with('author:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (PortalMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'created_at' => $m->created_at?->toIso8601String(),
                'from_customer' => $m->isFromCustomer(),
                'author_name' => $m->author?->name,
            ]);

        return Inertia::render('Company/CustomerChat/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, int $endUser): RedirectResponse
    {
        $this->authorizeCompanyStaff($request);

        $companyId = (int) $request->user()->company_id;

        $customer = User::query()
            ->where('company_id', $companyId)
            ->where('role', User::ROLE_END_USER)
            ->findOrFail($endUser);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        PortalMessage::query()->create([
            'company_id' => $companyId,
            'end_user_id' => $customer->id,
            'author_user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('status', __('Reply sent.'));
    }

    private function authorizeCompanyStaff(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isCompany() || $user->isStaff()) && $user->company_id, 403);
    }
}
