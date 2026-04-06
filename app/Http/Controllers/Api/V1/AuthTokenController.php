<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    /**
     * Issue a Sanctum personal access token for machine / integration clients.
     */
    public function store(Request $request): JsonResponse
    {
        $allowed = array_keys(config('banking_api.token_abilities', []));

        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in($allowed)],
        ]);

        $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($validated['email'])])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials are incorrect.')],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => __('Account is inactive.')], 403);
        }

        if ($user->isEndUser()) {
            return response()->json(['message' => __('API tokens are not issued for member portal accounts.')], 403);
        }

        $name = $validated['device_name'] ?? 'core-banking-api';

        $scopes = array_values(array_intersect($validated['scopes'] ?? [], $allowed));
        if ($scopes === []) {
            $scopes = ['banking:read'];
        }

        if (! $user->canCreateJournalEntries()) {
            $scopes = array_values(array_diff($scopes, ['banking:journal']));
        }

        if (! $user->isCompany() && ! $user->isAdmin()) {
            $scopes = array_values(array_diff($scopes, ['banking:webhooks:manage']));
        }

        if ($scopes === []) {
            $scopes = ['banking:read'];
        }

        $plain = $user->createToken($name, $scopes)->plainTextToken;

        return response()->json([
            'token' => $plain,
            'token_type' => 'Bearer',
            'abilities' => $scopes,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
            ],
        ]);
    }
}
