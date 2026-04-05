<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToCompany;

    public const VALUATION_FIFO = 'fifo';

    public const VALUATION_LIFO = 'lifo';

    /**
     * @return list<string>
     */
    public static function valuationMethods(): array
    {
        return [
            self::VALUATION_FIFO,
            self::VALUATION_LIFO,
        ];
    }

    protected $fillable = [
        'company_id',
        'sku',
        'name',
        'quantity',
        'unit_cost_cents',
        'notes',
        'valuation_method',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class, 'inventory_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_item_id');
    }

    /**
     * Closing stock at cost from remaining lots (FIFO/LIFO layers).
     */
    public function valueAtCostCents(): int
    {
        if ($this->lots()->exists()) {
            return (int) $this->lots()
                ->where('quantity_remaining', '>', 0)
                ->get()
                ->sum(fn (InventoryLot $lot) => $lot->valueRemainingAtCostCents());
        }

        return (int) round(((float) $this->quantity) * (int) $this->unit_cost_cents);
    }
}
