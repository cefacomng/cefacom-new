<?php

namespace App\Support\Demo;

/**
 * DEVELOPMENT-ONLY sample data used by the design-system page to show tables, filters and pagination.
 * Never rendered outside local/testing (the route is gated by ShellPreview) and never used for real figures.
 * Amounts are integer kobo.
 */
class DemoData
{
    /** @return list<array{id: int, name: string, channel: string, recipients: int, status: string, amountKobo: int, createdAt: string}> */
    public static function campaigns(): array
    {
        $names = [
            'Black Friday early access', 'Ramadan promo reminder', 'New branch opening', 'Weekend flash sale',
            'Salary week offer', 'Customer survey invite', 'Loyalty points update', 'Delivery delay notice',
            'December thank-you', 'Back to school offer', 'Product restock alert', 'Invoice reminder batch 4',
            'Church programme notice', 'Webinar invitation', 'Easter discount', 'Appointment confirmations',
            'Welcome series: day 1', 'Welcome series: day 3', 'Eid greetings', 'Pickup ready notices',
            'Rent due reminder', 'Cooperative meeting', 'New menu launch',
        ];
        $statuses = ['Completed', 'Completed', 'Sending', 'Scheduled', 'Draft', 'Completed', 'Needs review'];
        $rows = [];
        foreach ($names as $i => $name) {
            $recipients = 120 + (($i * 377) % 4800);
            $channel = $i % 3 === 1 ? 'Email' : 'SMS';
            $unitKobo = $channel === 'SMS' ? 460 : 120;
            $rows[] = [
                'id' => $i + 1,
                'name' => $name,
                'channel' => $channel,
                'recipients' => $recipients,
                'status' => $statuses[$i % count($statuses)],
                'amountKobo' => $recipients * $unitKobo,
                'createdAt' => sprintf('2026-%02d-%02d', 10 - intdiv($i, 12), 28 - ($i % 27)),
            ];
        }

        return $rows;
    }
}
