<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CrmAccounts\CrmAccountResource;
use App\Filament\Resources\CrmActivities\CrmActivityResource;
use App\Filament\Resources\CrmContacts\CrmContactResource;
use App\Filament\Resources\CrmOpportunities\CrmOpportunityResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AdminDashboardStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->brandName('Ledger Admin')
            ->sidebarWidth('16rem')
            ->colors([
                'primary' => Color::Emerald,
                'gray' => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): View => view('filament.hooks.admin-topbar-context'),
            )
            ->resources([
                CompanyResource::class,
                UserResource::class,
                CrmAccountResource::class,
                CrmContactResource::class,
                CrmOpportunityResource::class,
                CrmActivityResource::class,
                ReviewResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AdminDashboardStatsWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
