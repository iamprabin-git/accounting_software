<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ChartAccount;
use App\Models\ChartAccountTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(ChartAccountTemplateSeeder::class);

        Admin::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $company = Company::query()->create([
            'name' => 'Demo Company',
            'address' => "100 Ledger Lane\nDemo City, DC 10001",
            'phone' => '+1 (555) 010-0200',
        ]);

        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'platform-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $owner = User::factory()->companyOwner($company)->create([
            'name' => 'Company Owner',
            'email' => 'company@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->staff($company)->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->endUser($company)->create([
            'name' => 'End User',
            'email' => 'enduser@example.com',
            'password' => Hash::make('password'),
        ]);

        $defaults = [
            ['code' => '1000', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Operating Expense', 'type' => 'expense'],
        ];

        $templatesByCode = ChartAccountTemplate::query()->get()->keyBy('code');

        foreach ($defaults as $row) {
            $template = $templatesByCode->get($row['code']);
            ChartAccount::query()->create([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'chart_account_template_id' => $template?->id,
                'approval_status' => ChartAccount::STATUS_APPROVED,
                'approved_at' => now(),
                ...$row,
            ]);
        }

        $inventoryAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('code', '1200')
            ->first();

        if ($inventoryAccount) {
            $company->inventory_chart_account_id = $inventoryAccount->id;
            $company->save();
        }
    }
}
