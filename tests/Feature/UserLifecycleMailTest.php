<?php

namespace Tests\Feature;

use App\Mail\AdminUserAccountCreatedMail;
use App\Mail\AdminUserPasswordChangedMail;
use App\Mail\UserAccountCreatedMail;
use App\Mail\UserPasswordChangedMail;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserLifecycleMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_triggers_user_and_admin_account_created_mails(): void
    {
        Mail::fake();

        Admin::query()->create([
            'name' => 'Panel Admin',
            'email' => 'panel-admin@example.test',
            'password' => Hash::make('secret'),
        ]);

        User::factory()->create([
            'email' => 'member@example.test',
        ]);

        Mail::assertSent(UserAccountCreatedMail::class, fn (UserAccountCreatedMail $mail): bool => $mail->user->email === 'member@example.test');

        Mail::assertSent(AdminUserAccountCreatedMail::class, fn (AdminUserAccountCreatedMail $mail): bool => $mail->user->email === 'member@example.test');
    }

    public function test_password_change_triggers_user_and_admin_password_mails(): void
    {
        Mail::fake();

        Admin::query()->create([
            'name' => 'Panel Admin',
            'email' => 'panel-admin@example.test',
            'password' => Hash::make('secret'),
        ]);

        $user = User::factory()->create();

        Mail::fake();

        $user->update([
            'password' => Hash::make('another-password'),
        ]);

        Mail::assertSent(UserPasswordChangedMail::class, fn (UserPasswordChangedMail $mail): bool => $mail->user->is($user));
        Mail::assertSent(AdminUserPasswordChangedMail::class, fn (AdminUserPasswordChangedMail $mail): bool => $mail->user->is($user));
    }
}
