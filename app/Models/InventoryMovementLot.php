<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementLot extends Model
{
    protected $fillable = [
        'inventory_movement_id',
        'inventory_lot_id',
        'quantity',
        'unit_cost_cents',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}
