<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TellerDayClose extends Model
{
    use BelongsToCompany;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'user_id',
        'close_date',
        'day_status',
        'opening_cash_cents',
        'counted_cash_cents',
        'expected_cash_cents',
        'vault_opening_cash_cents',
        'cash_received_cents',
        'system_cash_cents',
        'vault_returned_cash_cents',
        'closing_error_cents',
        'memo',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'close_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->day_status === self::STATUS_OPEN;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
