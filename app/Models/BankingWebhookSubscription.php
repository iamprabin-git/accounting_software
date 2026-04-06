<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankingWebhookSubscription extends Model
{
    use BelongsToCompany;

    protected $table = 'banking_webhook_subscriptions';

    protected $fillable = [
        'company_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'secret' => 'encrypted',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function wantsEvent(string $event): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $events = $this->events ?? [];

        return in_array($event, $events, true);
    }
}
