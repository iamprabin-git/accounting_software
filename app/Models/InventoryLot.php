<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLot extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'inventory_item_id',
        'inventory_movement_id',
        'quantity_remaining',
        'quantity_original',
        'unit_cost_cents',
        'received_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'quantity_remaining' => 'decimal:4',
            'quantity_original' => 'decimal:4',
            'received_at' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function purchaseMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function movementAllocations(): HasMany
    {
        return $this->hasMany(InventoryMovementLot::class, 'inventory_lot_id');
    }

    public function valueRemainingAtCostCents(): int
    {
        return (int) round(((float) $this->quantity_remaining) * (int) $this->unit_cost_cents);
    }
}
