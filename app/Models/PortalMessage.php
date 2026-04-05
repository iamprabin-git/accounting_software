<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalMessage extends Model
{
    use BelongsToCompany;

    protected $table = 'portal_messages';

    protected $fillable = [
        'company_id',
        'end_user_id',
        'author_user_id',
        'body',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function endUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'end_user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function isFromCustomer(): bool
    {
        return (int) $this->author_user_id === (int) $this->end_user_id;
    }
}
