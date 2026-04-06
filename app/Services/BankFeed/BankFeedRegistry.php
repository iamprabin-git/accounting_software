<?php

namespace App\Services\BankFeed;

class BankFeedRegistry
{
    /**
     * @return list<array{key: string, label: string, enabled: bool, configured: bool}>
     */
    public static function providersForFrontend(): array
    {
        $out = [];
        foreach (config('bank_feeds', []) as $key => $cfg) {
            if (! is_array($cfg) || ! isset($cfg['label'])) {
                continue;
            }
            $enabled = (bool) ($cfg['enabled'] ?? false);
            $configured = $enabled && self::isConfigured($key, $cfg);
            $out[] = [
                'key' => (string) $key,
                'label' => (string) $cfg['label'],
                'enabled' => $enabled,
                'configured' => $configured,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    protected static function isConfigured(string $key, array $cfg): bool
    {
        return match ($key) {
            'plaid' => ($cfg['client_id'] ?? '') !== '' && ($cfg['secret'] ?? '') !== '',
            'truelayer' => ($cfg['client_id'] ?? '') !== '' && ($cfg['client_secret'] ?? '') !== '',
            default => false,
        };
    }

    public static function anyProviderReady(): bool
    {
        foreach (self::providersForFrontend() as $p) {
            if ($p['configured']) {
                return true;
            }
        }

        return false;
    }
}
