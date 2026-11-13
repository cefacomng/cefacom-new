import { Hammer } from 'lucide-react';
import { AdminLayout } from '@/components/layout/ShellLayout';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/Feedback';
import { PageHeader } from '@/components/ui/PageHeader';

export default function AdminPlaceholder({ title, batch }: { title: string; batch: number | null }) {
    return (
        <AdminLayout title={title}>
            <PageHeader title={title} />
            <Card>
                <EmptyState
                    headingLevel="h2"
                    icon={Hammer}
                    title={`${title} is not built yet`}
                    description={batch ? `This admin section is delivered in Batch ${batch}. Navigation and layout are ready.` : 'Navigation and layout are ready.'}
                />
            </Card>
        </AdminLayout>
    );
}
