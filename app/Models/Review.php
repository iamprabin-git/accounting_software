<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'author_name',
        'author_email',
        'title',
        'body',
        'rating',
        'status',
        'admin_notes',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
