<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamUserController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = $request->user()->company_id;

        $base = User::query()
            ->where('company_id', $companyId)
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name');

        $staffMembers = (clone $base)
            ->where('role', User::ROLE_STAFF)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'portal_approved_at']);

        $endUserMembers = (clone $base)
            ->where('role', User::ROLE_END_USER)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'portal_approved_at']);

        return Inertia::render('Company/Team/Index', [
            'staffMembers' => $staffMembers,
            'endUserMembers' => $endUserMembers,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Company/Team/Create', [
            'roles' => User::companyAssignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $request->merge([
            'email' => EmailAddress::normalize($request->string('email')->toString()) ?? '',
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', EmailAddress::laravelRule(), 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(User::companyAssignableRoles())],
        ]);

        $isStaff = $validated['role'] === User::ROLE_STAFF;

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $companyId,
            'role' => $validated['role'],
            'is_active' => ! $isStaff,
            'portal_approved_at' => null,
            'portal_approved_by_user_id' => null,
        ]);

        return redirect()->route('company.team.index')
            ->with(
                'status',
                $isStaff
                    ? __('Staff user created. Activate their account when they are ready to sign in.')
                    : __('Team member created.')
            );
    }

    public function edit(Request $request, User $member): Response
    {
        $this->authorizeMember($request, $member);

        return Inertia::render('Company/Team/Edit', [
            'member' => $member->only(['id', 'name', 'email', 'role', 'is_active']),
            'roles' => User::companyAssignableRoles(),
        ]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);

        $request->merge([
            'email' => EmailAddress::normalize($request->string('email')->toString()) ?? '',
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', EmailAddress::laravelRule(), Rule::unique(User::class)->ignore($member->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(User::companyAssignableRoles())],
            'is_active' => ['required', 'boolean'],
        ]);

        $member->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ]);

        if (! empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }

        $member->save();

        return redirect()->route('company.team.index')
            ->with('status', __('Team member updated.'));
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);

        $member->delete();

        return redirect()->route('company.team.index')
            ->with('status', __('Team member removed.'));
    }

    public function activate(Request $request, User $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);
        abort_unless($member->role === User::ROLE_STAFF, 403);

        if ($member->is_active) {
            return back()->with('status', __('This account is already active.'));
        }

        $member->forceFill(['is_active' => true])->save();

        return back()->with('status', __('Staff account activated. They can sign in now.'));
    }

    public function approvePortal(Request $request, User $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);
        abort_unless($member->role === User::ROLE_END_USER, 403);

        $member->forceFill([
            'portal_approved_at' => now(),
            'portal_approved_by_user_id' => $request->user()->id,
        ])->save();

        return back()->with('status', __('Customer portal access approved.'));
    }

    public function revokePortal(Request $request, User $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);
        abort_unless($member->role === User::ROLE_END_USER, 403);

        $member->forceFill([
            'portal_approved_at' => null,
            'portal_approved_by_user_id' => null,
        ])->save();

        return back()->with('status', __('Customer portal access revoked.'));
    }

    private function authorizeMember(Request $request, User $member): void
    {
        if ($member->company_id !== $request->user()->company_id) {
            abort(403);
        }

        if (! in_array($member->role, User::companyAssignableRoles(), true)) {
            abort(403);
        }
    }
}
