<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integration model (not full OAuth2)
    |--------------------------------------------------------------------------
    |
    | Machine access uses Laravel Sanctum personal access tokens with named
    | abilities below (OAuth-style scopes). There is no separate authorization
    | server or third-party OAuth clients table; issue tokens via POST
    | /api/v1/auth/token or the company Integrations UI.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Token abilities (scopes)
    |--------------------------------------------------------------------------
    */
    'token_abilities' => [
        'banking:read' => 'Read summary, members, and positions',
        'banking:journal' => 'Post two-line journals and transfers (GL)',
        'banking:webhooks:manage' => 'Create, list, and delete webhook subscriptions via API',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook event types clients may subscribe to
    |--------------------------------------------------------------------------
    */
    'webhook_events' => [
        'journal.posted' => 'Journal auto-posted to ledger (approved immediately)',
    ],

];
