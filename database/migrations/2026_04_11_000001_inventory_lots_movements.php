<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'valuation_method')) {
                $table->string('valuation_method', 8)->default('fifo')->after('notes');
            }
        });

        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 16);
                $table->decimal('quantity', 15, 4);
                $table->date('transaction_date');
                $table->bigInteger('total_cost_cents');
                $table->string('reference', 128)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(
                    ['company_id', 'inventory_item_id', 'transaction_date'],
                    'inv_mov_co_item_dt_idx',
                );
            });
        } else {
            $this->ensureShortIndex(
                'inventory_movements',
                ['company_id', 'inventory_item_id', 'transaction_date'],
                'inv_mov_co_item_dt_idx',
            );
        }

        if (! Schema::hasTable('inventory_lots')) {
            Schema::create('inventory_lots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
                $table->decimal('quantity_remaining', 15, 4);
                $table->decimal('quantity_original', 15, 4);
                $table->unsignedBigInteger('unit_cost_cents');
                $table->date('received_at');
                $table->string('reference', 128)->nullable();
                $table->timestamps();

                $table->index(
                    ['inventory_item_id', 'received_at', 'id'],
                    'inv_lots_item_recv_idx',
                );
            });
        } else {
            $this->ensureShortIndex(
                'inventory_lots',
                ['inventory_item_id', 'received_at', 'id'],
                'inv_lots_item_recv_idx',
            );
        }

        if (! Schema::hasTable('inventory_movement_lots')) {
            Schema::create('inventory_movement_lots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_movement_id')->constrained('inventory_movements')->cascadeOnDelete();
                $table->foreignId('inventory_lot_id')->constrained('inventory_lots')->cascadeOnDelete();
                $table->decimal('quantity', 15, 4);
                $table->unsignedBigInteger('unit_cost_cents');
                $table->timestamps();

                $table->index('inventory_movement_id', 'inv_mov_lots_mov_id_idx');
            });
        }

        $this->migrateExistingInventoryToLots();
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureShortIndex(string $table, array $columns, string $indexName): void
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $rows = $connection->select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName],
        );

        if (count($rows) > 0) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function migrateExistingInventoryToLots(): void
    {
        if (! Schema::hasTable('inventory_lots') || ! Schema::hasTable('inventory_movements')) {
            return;
        }

        $items = DB::table('inventory_items')
            ->where('quantity', '>', 0)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('inventory_lots')
                    ->whereColumn('inventory_lots.inventory_item_id', 'inventory_items.id');
            })
            ->get();

        foreach ($items as $row) {
            $qty = (float) $row->quantity;
            if ($qty <= 0) {
                continue;
            }

            $unitCost = (int) $row->unit_cost_cents;
            $totalCents = (int) round($qty * $unitCost);

            $movementId = DB::table('inventory_movements')->insertGetId([
                'company_id' => $row->company_id,
                'inventory_item_id' => $row->id,
                'user_id' => null,
                'type' => 'purchase',
                'quantity' => $qty,
                'transaction_date' => now()->toDateString(),
                'total_cost_cents' => $totalCents,
                'reference' => 'Opening balance',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_lots')->insert([
                'company_id' => $row->company_id,
                'inventory_item_id' => $row->id,
                'inventory_movement_id' => $movementId,
                'quantity_remaining' => $qty,
                'quantity_original' => $qty,
                'unit_cost_cents' => $unitCost,
                'received_at' => now()->toDateString(),
                'reference' => 'Opening balance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movement_lots');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_movements');

        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'valuation_method')) {
                $table->dropColumn('valuation_method');
            }
        });
    }
};
