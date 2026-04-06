<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\AuthTokenRevokeController;
use App\Http\Controllers\Api\V1\BankingJournalWriteController;
use App\Http\Controllers\Api\V1\BankingMemberController;
use App\Http\Controllers\Api\V1\BankingPositionController;
use App\Http\Controllers\Api\V1\BankingSummaryController;
use App\Http\Controllers\Api\V1\BankingWebhookSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| Core banking API (Bearer Sanctum tokens). Scopes: banking:read, banking:journal, banking:webhooks:manage
| Issue: POST /api/v1/auth/token  |  Revoke: DELETE /api/v1/auth/tokens/current or /{id}
| Platform admin: pass company_id on every banking request (query, JSON body, or X-Company-Id).
*/

Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/auth/tokens/current', [AuthTokenRevokeController::class, 'revokeCurrent'])
            ->name('api.v1.auth.tokens.revoke-current');
        Route::delete('/auth/tokens/{token}', [AuthTokenRevokeController::class, 'destroy'])
            ->whereNumber('token')
            ->name('api.v1.auth.tokens.destroy');
    });

    Route::middleware([
        'auth:sanctum',
        'abilities_any:banking:read,banking:journal',
        'banking.api.company',
    ])
        ->prefix('banking')
        ->group(function () {
            Route::get('/summary', BankingSummaryController::class)->name('api.v1.banking.summary');
            Route::get('/members', [BankingMemberController::class, 'index'])->name('api.v1.banking.members');
            Route::get('/positions', [BankingPositionController::class, 'index'])->name('api.v1.banking.positions');
        });

    Route::middleware([
        'auth:sanctum',
        'abilities:banking:journal',
        'banking.api.company',
    ])
        ->prefix('banking')
        ->group(function () {
            Route::post('/journals/two-line', [BankingJournalWriteController::class, 'storeTwoLine'])
                ->name('api.v1.banking.journals.two-line');
            Route::post('/transfers', [BankingJournalWriteController::class, 'storeTransfer'])
                ->name('api.v1.banking.transfers');
        });

    Route::middleware([
        'auth:sanctum',
        'abilities:banking:webhooks:manage',
        'banking.api.company',
    ])
        ->prefix('banking/webhooks')
        ->group(function () {
            Route::get('/', [BankingWebhookSubscriptionController::class, 'index'])->name('api.v1.banking.webhooks.index');
            Route::post('/', [BankingWebhookSubscriptionController::class, 'store'])->name('api.v1.banking.webhooks.store');
            Route::delete('/{webhook}', [BankingWebhookSubscriptionController::class, 'destroy'])
                ->whereNumber('webhook')
                ->name('api.v1.banking.webhooks.destroy');
        });
});
