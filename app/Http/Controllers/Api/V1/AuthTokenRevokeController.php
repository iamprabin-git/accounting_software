<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenRevokeController extends Controller
{
    public function revokeCurrent(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => __('No personal access token in use.')], 400);
        }

        $token->delete();

        return response()->json(['message' => 'Token revoked.']);
    }

    public function destroy(Request $request, int $token): JsonResponse
    {
        $deleted = $request->user()
            ->tokens()
            ->whereKey($token)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
