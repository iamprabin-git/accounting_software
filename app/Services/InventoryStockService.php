<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryMovementLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryStockService
{
    public function recordPurchase(
        InventoryItem $item,
        float $quantity,
        int $unitCostCents,
        string $transactionDate,
        ?string $reference,
        ?string $notes,
        ?User $user,
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(__('Purchase quantity must be positive.'));
        }

        if ($unitCostCents < 0) {
            throw new InvalidArgumentException(__('Unit cost cannot be negative.'));
        }

        $extensionCents = (int) round($quantity * $unitCostCents);

        return DB::transaction(function () use ($item, $quantity, $unitCostCents, $transactionDate, $reference, $notes, $user, $extensionCents) {
            $movement = InventoryMovement::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'user_id' => $user?->id,
                'type' => InventoryMovement::TYPE_PURCHASE,
                'quantity' => $quantity,
                'transaction_date' => $transactionDate,
                'total_cost_cents' => $extensionCents,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            InventoryLot::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'inventory_movement_id' => $movement->id,
                'quantity_remaining' => $quantity,
                'quantity_original' => $quantity,
                'unit_cost_cents' => $unitCostCents,
                'received_at' => $transactionDate,
                'reference' => $reference,
            ]);

            $this->syncItemRollups($item->fresh());

            return $movement->fresh();
        });
    }

    public function recordSale(
        InventoryItem $item,
        float $quantity,
        string $transactionDate,
        ?string $reference,
        ?string $notes,
        ?User $user,
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(__('Sale quantity must be positive.'));
        }

        return DB::transaction(function () use ($item, $quantity, $transactionDate, $reference, $notes, $user) {
            $locked = InventoryLot::query()
                ->where('inventory_item_id', $item->id)
                ->where('company_id', $item->company_id)
                ->where('quantity_remaining', '>', 0)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $fifo = $item->valuation_method !== InventoryItem::VALUATION_LIFO;
            $ordered = $fifo
                ? $locked->sortBy([
                    ['received_at', 'asc'],
                    ['id', 'asc'],
                ])->values()
                : $locked->sortBy([
                    ['received_at', 'desc'],
                    ['id', 'desc'],
                ])->values();

            $toSell = $quantity;
            $cogsCents = 0;
            $plan = [];

            foreach ($ordered as $lot) {
                if ($toSell <= 0) {
                    break;
                }

                $remaining = (float) $lot->quantity_remaining;
                if ($remaining <= 0) {
                    continue;
                }

                $take = min($remaining, $toSell);
                $lineCents = (int) round($take * (int) $lot->unit_cost_cents);
                $cogsCents += $lineCents;
                $plan[] = ['lot' => $lot, 'take' => $take, 'unit_cost_cents' => (int) $lot->unit_cost_cents];
                $toSell -= $take;
            }

            if ($this->floatGt($toSell, 0)) {
                throw new InvalidArgumentException(__('Not enough stock on hand for this sale.'));
            }

            $movement = InventoryMovement::query()->create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'user_id' => $user?->id,
                'type' => InventoryMovement::TYPE_SALE,
                'quantity' => $quantity,
                'transaction_date' => $transactionDate,
                'total_cost_cents' => $cogsCents,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            foreach ($plan as $row) {
                /** @var InventoryLot $lot */
                $lot = $row['lot'];
                $take = $row['take'];

                InventoryMovementLot::query()->create([
                    'inventory_movement_id' => $movement->id,
                    'inventory_lot_id' => $lot->id,
                    'quantity' => $take,
                    'unit_cost_cents' => $row['unit_cost_cents'],
                ]);

                $newRemaining = round(((float) $lot->quantity_remaining) - $take, 4);
                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $lot->update(['quantity_remaining' => $newRemaining]);
            }

            $this->syncItemRollups($item->fresh());

            return $movement->fresh(['lotAllocations']);
        });
    }

    private function syncItemRollups(InventoryItem $item): void
    {
        $lots = InventoryLot::query()
            ->where('inventory_item_id', $item->id)
            ->where('company_id', $item->company_id)
            ->where('quantity_remaining', '>', 0)
            ->get();

        $qty = 0.0;
        $valueCents = 0;

        foreach ($lots as $lot) {
            $q = (float) $lot->quantity_remaining;
            $qty += $q;
            $valueCents += (int) round($q * (int) $lot->unit_cost_cents);
        }

        $item->quantity = $qty;
        $item->unit_cost_cents = $qty > 0 ? (int) round($valueCents / $qty) : 0;
        $item->saveQuietly();
    }

    private function floatGt(float $a, float $b): bool
    {
        return ($a - $b) > 0.0001;
    }
}
