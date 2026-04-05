<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\CrmTenantGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmActivity extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::saving(function (CrmActivity $activity): void {
            CrmTenantGuard::assertActivity($activity);
        });
    }

    public const TYPE_CALL = 'call';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_TASK = 'task';

    public const TYPE_NOTE = 'note';

    public const TYPE_EMAIL = 'email';

    protected $table = 'crm_activities';

    protected $fillable = [
        'company_id',
        'type',
        'subject_type',
        'subject_id',
        'title',
        'body',
        'due_at',
        'completed_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_CALL => 'Call',
            self::TYPE_MEETING => 'Meeting',
            self::TYPE_TASK => 'Task',
            self::TYPE_NOTE => 'Note',
            self::TYPE_EMAIL => 'Email',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
