<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-company portable SQLite snapshots
    |--------------------------------------------------------------------------
    |
    | Daily (or on-demand) files under storage/app/{storage_subdir}/{company_id}/Y-m-d.sqlite
    | plus manifest.json — easy to copy to USB or another PC. Not a full application backup
    | (no sessions, queue, or other tenants).
    |
    */

    'storage_subdir' => env('COMPANY_PORTABLE_DB_DIR', 'company-portable-databases'),

    'retain_days' => (int) env('COMPANY_PORTABLE_DB_RETAIN_DAYS', 90),

    'schedule_enabled' => filter_var(
        env('COMPANY_PORTABLE_DB_SCHEDULE', true),
        FILTER_VALIDATE_BOOL,
    ),

];
