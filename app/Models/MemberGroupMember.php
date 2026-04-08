<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberGroupMember extends Model
{
    protected $fillable = [
        'member_group_id',
        'member_id',
    ];

    public function memberGroup(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class, 'member_group_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
