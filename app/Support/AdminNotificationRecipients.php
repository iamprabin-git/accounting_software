<?php

namespace App\Support;

use App\Models\Admin;

class AdminNotificationRecipients
{
    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        $fromConfig = config('mail.admin_notification_emails', []);
        $fromConfig = is_array($fromConfig) ? $fromConfig : [];

        $fromAdmins = Admin::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn (string $e) => trim($e))
            ->filter()
            ->all();

        $merged = array_merge($fromAdmins, $fromConfig);

        return array_values(array_unique(array_filter($merged)));
    }
}
