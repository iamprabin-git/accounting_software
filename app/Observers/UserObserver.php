<?php

namespace App\Observers;

use App\Mail\AdminUserAccountCreatedMail;
use App\Mail\AdminUserPasswordChangedMail;
use App\Mail\UserAccountCreatedMail;
use App\Mail\UserPasswordChangedMail;
use App\Models\User;
use App\Support\AdminNotificationRecipients;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->email === null || $user->email === '') {
            return;
        }

        try {
            Mail::to($user->email)->send(new UserAccountCreatedMail($user));

            foreach (AdminNotificationRecipients::emails() as $adminEmail) {
                Mail::to($adminEmail)->send(new AdminUserAccountCreatedMail($user));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged('password')) {
            return;
        }

        if ($user->email === null || $user->email === '') {
            return;
        }

        $ip = request()?->ip();

        try {
            Mail::to($user->email)->send(new UserPasswordChangedMail($user, $ip));

            foreach (AdminNotificationRecipients::emails() as $adminEmail) {
                Mail::to($adminEmail)->send(new AdminUserPasswordChangedMail($user, $ip));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
