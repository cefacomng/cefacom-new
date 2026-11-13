<?php

namespace App\Support;

use App\Enums\PermissionKey;
use App\Models\User;

/**
 * Single source of truth for navigation. Rendered by the frontend; Batch 2 will filter by
 * permission on the server so people never receive links they cannot use.
 */
class Navigation
{
    /** @var list<array{key: string, label: string, icon: string, batch: int|null}> */
    private const CUSTOMER = [
        ['key' => 'overview', 'label' => 'Overview', 'icon' => 'layout-dashboard', 'batch' => null],
        ['key' => 'sms', 'label' => 'SMS', 'icon' => 'message-square', 'batch' => 6],
        ['key' => 'email', 'label' => 'Email', 'icon' => 'mail', 'batch' => 7],
        ['key' => 'contacts', 'label' => 'Contacts', 'icon' => 'users', 'batch' => 3],
        ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bar-chart', 'batch' => 8],
        ['key' => 'wallet', 'label' => 'Wallet & Transactions', 'icon' => 'wallet', 'batch' => 4],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'batch' => 2],
    ];

    /** @var list<array{key: string, label: string, icon: string, batch: int|null, permission?: PermissionKey}> */
    private const ADMIN = [
        ['key' => 'overview', 'label' => 'Overview', 'icon' => 'layout-dashboard', 'batch' => null],
        ['key' => 'customers', 'label' => 'Customers', 'icon' => 'users', 'batch' => 9, 'permission' => PermissionKey::ViewCustomers],
        ['key' => 'campaigns', 'label' => 'Campaigns', 'icon' => 'megaphone', 'batch' => 9, 'permission' => PermissionKey::ViewCampaigns],
        ['key' => 'messages', 'label' => 'Messages', 'icon' => 'message-square', 'batch' => 9, 'permission' => PermissionKey::ViewCampaigns],
        ['key' => 'wallet', 'label' => 'Wallet & Payments', 'icon' => 'wallet', 'batch' => 9, 'permission' => PermissionKey::ViewPayments],
        ['key' => 'pricing', 'label' => 'Pricing', 'icon' => 'tag', 'batch' => 9, 'permission' => PermissionKey::ChangePricing],
        ['key' => 'sms-providers', 'label' => 'SMS Providers', 'icon' => 'radio', 'batch' => 9, 'permission' => PermissionKey::ManageProviders],
        ['key' => 'email-providers', 'label' => 'Email Providers', 'icon' => 'send', 'batch' => 9, 'permission' => PermissionKey::ManageProviders],
        ['key' => 'sender-ids', 'label' => 'Sender IDs', 'icon' => 'badge-check', 'batch' => 9, 'permission' => PermissionKey::ManageSenderIds],
        ['key' => 'sending-domains', 'label' => 'Sending Domains', 'icon' => 'globe', 'batch' => 9, 'permission' => PermissionKey::ManageDomains],
        ['key' => 'staff', 'label' => 'Staff & Permissions', 'icon' => 'shield', 'batch' => 9, 'permission' => PermissionKey::ManageStaff],
        ['key' => 'audit-logs', 'label' => 'Audit Logs', 'icon' => 'scroll-text', 'batch' => 9, 'permission' => PermissionKey::ViewAuditLogs],
        ['key' => 'system-settings', 'label' => 'System Settings', 'icon' => 'settings', 'batch' => 9, 'permission' => PermissionKey::ManageSystemSettings],
    ];

    /** @return list<array{key: string, label: string, href: string, icon: string, batch: int|null}> */
    public static function customer(): array
    {
        return self::build(self::CUSTOMER, 'app');
    }

    /**
     * Admin links. When a user is given, only sections that user may open are returned,
     * so people never receive links they cannot use (the routes enforce the same rule).
     *
     * @return list<array{key: string, label: string, href: string, icon: string, batch: int|null}>
     */
    public static function admin(?User $user = null): array
    {
        $items = self::ADMIN;
        if ($user !== null) {
            $items = array_values(array_filter($items, fn (array $i) => ! isset($i['permission']) || $user->hasPermission($i['permission'])));
        }

        return self::build($items, 'admin');
    }

    public static function permissionFor(string $key): ?PermissionKey
    {
        foreach (self::ADMIN as $item) {
            if ($item['key'] === $key) {
                return $item['permission'] ?? null;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function keys(string $area): array
    {
        return array_column($area === 'admin' ? self::ADMIN : self::CUSTOMER, 'key');
    }

    /** @return array{key: string, label: string, batch: int|null}|null */
    public static function find(string $area, string $key): ?array
    {
        foreach ($area === 'admin' ? self::ADMIN : self::CUSTOMER as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }

    /** @param  list<array<string, mixed>>  $items */
    private static function build(array $items, string $area): array
    {
        return array_map(fn (array $i) => [
            'key' => $i['key'],
            'label' => $i['label'],
            'href' => $i['key'] === 'overview' ? "/{$area}" : "/{$area}/{$i['key']}",
            'icon' => $i['icon'],
            'batch' => $i['batch'],
        ], $items);
    }
}
