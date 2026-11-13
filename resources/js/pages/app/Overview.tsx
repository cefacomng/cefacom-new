import { usePage } from '@inertiajs/react';
import { Megaphone, Receipt } from 'lucide-react';
import { AppLayout } from '@/components/layout/ShellLayout';
import { buttonClasses } from '@/components/ui/Button';
import { Card, StatTile } from '@/components/ui/Card';
import { Alert, EmptyState } from '@/components/ui/Feedback';
import { PageHeader } from '@/components/ui/PageHeader';
import { formatNaira } from '@/lib/money';
import type { SharedProps } from '@/types';

/**
 * Overview structure. Every figure must come from real records (Batches 3-8); until then tiles
 * show a neutral dash and lists show empty states. Nothing here is invented.
 */
export default function Overview() {
    const { wallet } = usePage<SharedProps>().props;

    return (
        <AppLayout title="Overview">
            <PageHeader title="Overview" description="Your wallet, messages and campaigns at a glance." />

            <div className="mb-6">
                <Alert tone="info" title="Application shell">Figures appear here from real records as each feature is built.</Alert>
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <div className="space-y-6 xl:col-span-2">
                    <Card title="Wallet" actions={<a href="/app/wallet" className={buttonClasses('primary', 'sm')}>Fund Wallet</a>}>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <StatTile label="Available balance" value={wallet ? formatNaira(wallet.availableKobo) : null} />
                            <StatTile label="Reserved for campaigns" value={wallet ? formatNaira(wallet.reservedKobo) : null} />
                        </div>
                        <h3 className="mb-1 mt-6 text-sm font-semibold text-slate-800">Recent wallet activity</h3>
                        <EmptyState icon={Receipt} title="No wallet activity yet" description="Funding and message charges will be listed here." />
                    </Card>

                    <Card title="Messaging" description="Submitted means a provider accepted the message. It does not mean the recipient received it.">
                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">
                            <StatTile label="SMS campaigns" value={null} />
                            <StatTile label="Email campaigns" value={null} />
                            <StatTile label="Messages submitted" value={null} />
                            <StatTile label="Messages delivered" value={null} />
                            <StatTile label="Messages failed" value={null} />
                            <StatTile label="Awaiting status confirmation" value={null} />
                        </div>
                    </Card>

                    <Card title="Recent campaigns">
                        <EmptyState icon={Megaphone} title="No campaigns yet" description="Campaigns you create will appear here with their recipients, status and cost." />
                    </Card>
                </div>

                <Card title="Quick actions">
                    <div className="grid gap-3">
                        <a href="/app/sms" className={buttonClasses('primary')}>Send SMS</a>
                        <a href="/app/email" className={buttonClasses('secondary')}>Create Email</a>
                        <a href="/app/contacts" className={buttonClasses('secondary')}>Add Contacts</a>
                        <a href="/app/wallet" className={buttonClasses('secondary')}>Fund Wallet</a>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
