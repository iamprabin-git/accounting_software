<?php

namespace App\Providers;

use App\Models\CrmAccount;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Relation::enforceMorphMap([
            'crm_account' => CrmAccount::class,
            'crm_contact' => CrmContact::class,
            'crm_opportunity' => CrmOpportunity::class,
        ]);

        User::observe(UserObserver::class);
    }
}
