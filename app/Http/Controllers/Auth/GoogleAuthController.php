<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        if (! AuthenticatedSessionController::googleAuthConfigured()) {
            abort(404);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! AuthenticatedSessionController::googleAuthConfigured()) {
            abort(404);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')
                ->with('status', 'Google sign-in was cancelled or expired. Please try again.');
        }

        $email = EmailAddress::normalize($googleUser->getEmail());
        if ($email === null || $email === '') {
            return redirect()->route('login')
                ->with('status', __('Google did not return a valid email address. Please use another sign-in method.'));
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);
            } else {
                $displayName = $googleUser->getName()
                    ?: ($googleUser->getNickname() ?: 'Google user');

                $company = Company::query()->create([
                    'name' => $displayName."'s organization",
                ]);

                $user = User::query()->create([
                    'name' => $displayName,
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(48)),
                    'company_id' => $company->id,
                    'role' => User::ROLE_COMPANY,
                ]);
            }
        }

        if (! $user->canAccessCustomerApp()) {
            return redirect()->route('login')
                ->with('status', __('Your account is inactive or your subscription has ended. Please contact support.'));
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
