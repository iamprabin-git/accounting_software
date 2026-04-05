<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ResolvesLoginCompanyBranding;
use App\Http\Controllers\Controller;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    use ResolvesLoginCompanyBranding;

    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
            ...$this->loginCompanyBrandingProps($request),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => EmailAddress::normalize($request->string('email')->toString()) ?? '',
        ]);

        $request->validate([
            'email' => ['required', 'string', 'max:255', EmailAddress::laravelRule()],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
