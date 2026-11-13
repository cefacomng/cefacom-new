import { AdminLayout } from '@/components/layout/ShellLayout';
import { Card, StatTile } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Feedback';
import { PageHeader } from '@/components/ui/PageHeader';

export default function AdminOverview() {
    return (
        <AdminLayout title="Overview">
            <PageHeader title="Admin overview" description="Operational view across customers, campaigns, messages and money." />
            <div className="mb-6">
                <Alert tone="info" title="Permissions apply">You only see the sections your role allows. The server checks every request.</Alert>
            </div>
            <Card title="Platform figures" description="Populated from real records in later batches.">
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatTile label="Customers" value={null} />
                    <StatTile label="Campaigns today" value={null} />
                    <StatTile label="Messages awaiting status" value={null} />
                    <StatTile label="Unreconciled items" value={null} />
                </div>
            </Card>
        </AdminLayout>
    );
}
