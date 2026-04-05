<?php

namespace Database\Seeders;

use App\Models\ChartAccountTemplate;
use Illuminate\Database\Seeder;

class ChartAccountTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => '1000', 'name' => 'Cash on hand', 'type' => ChartAccountTemplate::TYPE_ASSET, 'sort_order' => 10],
            ['code' => '1100', 'name' => 'Accounts receivable', 'type' => ChartAccountTemplate::TYPE_ASSET, 'sort_order' => 20],
            ['code' => '1200', 'name' => 'Inventory', 'type' => ChartAccountTemplate::TYPE_ASSET, 'sort_order' => 30],
            ['code' => '1300', 'name' => 'Prepaid expenses', 'type' => ChartAccountTemplate::TYPE_ASSET, 'sort_order' => 40],
            ['code' => '1500', 'name' => 'Equipment', 'type' => ChartAccountTemplate::TYPE_ASSET, 'sort_order' => 50],
            ['code' => '2000', 'name' => 'Accounts payable', 'type' => ChartAccountTemplate::TYPE_LIABILITY, 'sort_order' => 60],
            ['code' => '2100', 'name' => 'Accrued expenses', 'type' => ChartAccountTemplate::TYPE_LIABILITY, 'sort_order' => 70],
            ['code' => '3000', 'name' => 'Owner equity', 'type' => ChartAccountTemplate::TYPE_EQUITY, 'sort_order' => 80],
            ['code' => '3100', 'name' => 'Retained earnings', 'type' => ChartAccountTemplate::TYPE_EQUITY, 'sort_order' => 90],
            ['code' => '4000', 'name' => 'Sales revenue', 'type' => ChartAccountTemplate::TYPE_REVENUE, 'sort_order' => 100],
            ['code' => '5000', 'name' => 'Operating expense', 'type' => ChartAccountTemplate::TYPE_EXPENSE, 'sort_order' => 110],
            ['code' => '5100', 'name' => 'Cost of goods sold', 'type' => ChartAccountTemplate::TYPE_EXPENSE, 'sort_order' => 120],
        ];

        foreach ($rows as $row) {
            ChartAccountTemplate::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'description' => null,
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
