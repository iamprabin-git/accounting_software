<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\EmailAddress;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'googleAuthEnabled' => AuthenticatedSessionController::googleAuthConfigured(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => EmailAddress::normalize($request->string('email')->toString()) ?? '',
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', EmailAddress::laravelRule(), 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $company = Company::query()->create([
            'name' => $request->name."'s organization",
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
            'role' => User::ROLE_COMPANY,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
