<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    use BelongsToCompany;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    protected $fillable = [
        'company_id',
        'inventory_item_id',
        'user_id',
        'type',
        'quantity',
        'transaction_date',
        'total_cost_cents',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'transaction_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lotAllocations(): HasMany
    {
        return $this->hasMany(InventoryMovementLot::class, 'inventory_movement_id');
    }

    /** Lots added to stock by this purchase movement. */
    public function purchaseLots(): HasMany
    {
        return $this->hasMany(InventoryLot::class, 'inventory_movement_id');
    }
}
