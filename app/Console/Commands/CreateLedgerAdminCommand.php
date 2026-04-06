<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class CreateLedgerAdminCommand extends Command
{
    protected $signature = 'ledger-admin:create
                            {email? : Email (default: admin@example.com)}
                            {--password= : Plain password (default: password)}
                            {--force : Reset password if this email already exists}';

    protected $description = 'Create or reset the Filament Ledger Admin panel user (admins table)';

    public function handle(): int
    {
        $email = $this->argument('email') ?: 'admin@example.com';
        $plain = $this->option('password') ?: 'password';

        $existing = Admin::query()->where('email', $email)->first();

        if ($existing !== null && ! $this->option('force')) {
            $this->components->error("An admin with email [{$email}] already exists.");
            $this->line('Run with <fg=cyan>--force</> to reset the password, e.g.:');
            $this->line('  php artisan ledger-admin:create '.$email.' --password=your-password --force');

            return self::FAILURE;
        }

        if ($existing !== null) {
            $existing->password = $plain;
            $existing->save();
            $this->components->info("Password updated for [{$email}].");

            return self::SUCCESS;
        }

        Admin::query()->create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $plain,
        ]);

        $this->components->info("Ledger Admin created: [{$email}]");
        $this->line('Sign in at <fg=cyan>/admin</> with that email and password.');

        return self::SUCCESS;
    }
}
