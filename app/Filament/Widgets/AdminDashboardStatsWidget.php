<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CrmOpportunities\CrmOpportunityResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Company;
use App\Models\CrmOpportunity;
use App\Models\Review;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminDashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected ?string $heading = 'At a glance';

    protected ?string $description = 'Key metrics across organizations, owners, pipeline, and moderation.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $openOpportunities = CrmOpportunity::query()
            ->whereNotIn('stage', [CrmOpportunity::STAGE_WON, CrmOpportunity::STAGE_LOST])
            ->count();

        $pendingReviews = Review::query()->where('status', Review::STATUS_PENDING)->count();

        return [
            Stat::make('Organizations', number_format(Company::query()->count()))
                ->description('Active companies')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
                ->color('success')
                ->chart($this->sparkline(Company::class))
                ->url(CompanyResource::getUrl()),
            Stat::make('Company owners', number_format(User::query()->where('role', User::ROLE_COMPANY)->count()))
                ->description('Tenant administrator accounts')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->url(UserResource::getUrl()),
            Stat::make('Open opportunities', number_format($openOpportunities))
                ->description('CRM deals not won or lost')
                ->descriptionIcon(Heroicon::OutlinedCurrencyDollar)
                ->color('info')
                ->url(CrmOpportunityResource::getUrl()),
            Stat::make('Reviews to approve', number_format($pendingReviews))
                ->description($pendingReviews > 0 ? 'Awaiting moderation' : 'Queue is clear')
                ->descriptionIcon(Heroicon::OutlinedChatBubbleBottomCenterText)
                ->color($pendingReviews > 0 ? 'warning' : 'gray')
                ->url(ReviewResource::getUrl()),
        ];
    }

    /**
     * @param  class-string<Company>  $model
     * @return array<int, float>
     */
    protected function sparkline(string $model): array
    {
        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $points[] = (float) $model::query()
                ->where('created_at', '<=', $day->copy()->endOfDay())
                ->count();
        }

        return $points;
    }
}
