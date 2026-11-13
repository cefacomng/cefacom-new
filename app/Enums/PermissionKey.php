<?php

namespace App\Enums;

/**
 * Every staff permission. The backend checks these; hiding a button is not security.
 * Super Admins hold every permission implicitly (see User::hasPermission).
 */
enum PermissionKey: string
{
    case ViewCustomers = 'customers.view';
    case ManageCustomers = 'customers.manage';
    case ViewCampaigns = 'campaigns.view';
    case ManageCampaigns = 'campaigns.manage';
    case ViewPayments = 'payments.view';
    case ManageRefunds = 'refunds.manage';
    case ChangePricing = 'pricing.change';
    case ManageProviders = 'providers.manage';
    case ManageSenderIds = 'sender_ids.manage';
    case ManageDomains = 'domains.manage';
    case ViewAuditLogs = 'audit_logs.view';
    case ManageStaff = 'staff.manage';
    case ManageSystemSettings = 'system_settings.manage';

    public function label(): string
    {
        return match ($this) {
            self::ViewCustomers => 'View customers',
            self::ManageCustomers => 'Manage customers',
            self::ViewCampaigns => 'View campaigns',
            self::ManageCampaigns => 'Manage campaigns',
            self::ViewPayments => 'View payments',
            self::ManageRefunds => 'Manage refunds',
            self::ChangePricing => 'Change pricing',
            self::ManageProviders => 'Manage providers',
            self::ManageSenderIds => 'Manage sender IDs',
            self::ManageDomains => 'Manage domains',
            self::ViewAuditLogs => 'View audit logs',
            self::ManageStaff => 'Manage staff',
            self::ManageSystemSettings => 'Manage system settings',
        };
    }
}
