import { Plus } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { BareLayout } from '@/components/layout/BareLayout';
import { Badge, type BadgeTone } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { DataTable, type Column } from '@/components/ui/DataTable';
import { Checkbox, Input, PasswordInput, Select, Textarea } from '@/components/ui/Field';
import { Alert, EmptyState, ErrorState, LoadingBlock, Skeleton, Spinner } from '@/components/ui/Feedback';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { useToast } from '@/components/ui/Toast';
import { formatNaira } from '@/lib/money';

type DemoCampaign = {
    id: number;
    name: string;
    channel: string;
    recipients: number;
    status: string;
    amountKobo: number;
    createdAt: string;
};

const statusTone: Record<string, BadgeTone> = {
    Completed: 'success',
    Sending: 'info',
    Scheduled: 'neutral',
    Draft: 'neutral',
    'Needs review': 'warning',
};

const PER_PAGE = 8;

function Section({ id, title, children }: { id: string; title: string; children: ReactNode }) {
    return (
        <section id={id} aria-labelledby={`${id}-title`} className="space-y-4">
            <h2 id={`${id}-title`} className="text-lg font-semibold text-navy">{title}</h2>
            {children}
        </section>
    );
}

function Swatch({ name, hex, use, className }: { name: string; hex: string; use: string; className: string }) {
    return (
        <div className="flex items-center gap-3">
            <span className={`size-12 shrink-0 rounded-md border border-slate-200 ${className}`} aria-hidden="true" />
            <div className="text-sm">
                <p className="font-semibold text-slate-900">{name} <span className="font-normal text-slate-500">{hex}</span></p>
                <p className="text-slate-600">{use}</p>
            </div>
        </div>
    );
}

export default function DesignSystem({ demoCampaigns }: { demoCampaigns: DemoCampaign[] }) {
    const { notify } = useToast();
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [saving, setSaving] = useState(false);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        return demoCampaigns.filter((c) => (status === 'all' || c.status === status) && (q === '' || c.name.toLowerCase().includes(q)));
    }, [demoCampaigns, search, status]);

    const pageRows = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    const columns: Array<Column<DemoCampaign>> = [
        { key: 'name', header: 'Campaign', cell: (c) => <span className="font-medium text-slate-900">{c.name}</span> },
        { key: 'channel', header: 'Channel', cell: (c) => c.channel },
        { key: 'recipients', header: 'Recipients', align: 'right', cell: (c) => c.recipients.toLocaleString('en-NG') },
        { key: 'status', header: 'Status', cell: (c) => <Badge tone={statusTone[c.status] ?? 'neutral'}>{c.status}</Badge> },
        { key: 'amount', header: 'Amount', align: 'right', cell: (c) => formatNaira(c.amountKobo) },
        { key: 'created', header: 'Created', cell: (c) => c.createdAt },
    ];

    function resetTo(first: () => void) {
        first();
        setPage(1);
    }

    return (
        <BareLayout title="Design system">
            <PageHeader title="Design system" description="Every reusable building block, in one place." />
            <div className="mb-8">
                <Alert tone="warning" title="Demo data, development only">Rows in the table below are sample data supplied only in local development. They are never shown in production.</Alert>
            </div>

            <div className="space-y-12">
                <Section id="colour" title="Colour">
                    <Card>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Swatch name="Navy" hex="#01406d" use="Structure: sidebar, headings." className="bg-navy" />
                            <Swatch name="Teal (strong)" hex="#007c82" use="Primary actions. Brand teal #01b4ba is for focus and highlights." className="bg-teal-strong" />
                            <Swatch name="Orange" hex="#ff7a0f" use="Attention and warnings only." className="bg-orange" />
                            <Swatch name="Danger" hex="#b42318" use="Errors and destructive actions." className="bg-danger" />
                        </div>
                    </Card>
                </Section>

                <Section id="buttons" title="Buttons">
                    <Card>
                        <div className="flex flex-wrap items-center gap-3">
                            <Button>Primary</Button>
                            <Button variant="secondary">Secondary</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="danger">Danger</Button>
                            <Button size="sm">Small</Button>
                            <Button icon={<Plus className="size-4" aria-hidden="true" />}>With icon</Button>
                            <Button loading>Saving</Button>
                            <Button disabled>Disabled</Button>
                        </div>
                    </Card>
                </Section>

                <Section id="inputs" title="Inputs">
                    <Card>
                        <div className="grid gap-5 md:grid-cols-2">
                            <Input label="Campaign name" placeholder="Weekend flash sale" hint="Only you can see this name." />
                            <Input label="Phone number" defaultValue="0803 123" error="Enter a valid Nigerian phone number." />
                            <Select label="Channel" options={[{ value: 'sms', label: 'SMS' }, { value: 'email', label: 'Email' }]} />
                            <Input label="Disabled field" disabled defaultValue="Not editable" />
                            <PasswordInput label="Password" hint="Use the eye button to show or hide it." />
                            <div className="md:col-span-2">
                                <Textarea label="Message" placeholder="Type your message" hint="Character and segment counts appear here when SMS is built." />
                            </div>
                            <Checkbox label="I have permission to message these contacts" hint="Consent is required before sending." />
                        </div>
                    </Card>
                </Section>

                <Section id="badges" title="Status badges and alerts">
                    <Card>
                        <div className="mb-5 flex flex-wrap gap-2">
                            <Badge>Draft</Badge>
                            <Badge tone="info">Sending</Badge>
                            <Badge tone="success">Delivered</Badge>
                            <Badge tone="warning">Needs review</Badge>
                            <Badge tone="danger">Failed</Badge>
                        </div>
                        <div className="space-y-3">
                            <Alert tone="info" title="Information">A neutral note for the person.</Alert>
                            <Alert tone="success" title="Saved">Your changes were saved.</Alert>
                            <Alert tone="warning" title="Check this first">Orange is reserved for things that need attention.</Alert>
                            <Alert tone="danger" title="Could not complete">Something failed and needs action.</Alert>
                        </div>
                    </Card>
                </Section>

                <Section id="notifications" title="Notifications">
                    <Card description="Toasts announce to screen readers. Errors persist longer.">
                        <div className="flex flex-wrap gap-3">
                            <Button variant="secondary" onClick={() => notify({ tone: 'success', title: 'Contacts imported', description: '240 contacts added.' })}>Success</Button>
                            <Button variant="secondary" onClick={() => notify({ tone: 'info', title: 'Import started' })}>Info</Button>
                            <Button variant="secondary" onClick={() => notify({ tone: 'warning', title: 'Low balance', description: 'Fund your wallet before sending.' })}>Warning</Button>
                            <Button variant="secondary" onClick={() => notify({ tone: 'error', title: 'Payment not confirmed', description: 'Please try again.' })}>Error</Button>
                        </div>
                    </Card>
                </Section>

                <Section id="dialogs" title="Modal and confirmation">
                    <Card description="Financial actions always ask for confirmation and show the exact amount.">
                        <div className="flex flex-wrap gap-3">
                            <Button variant="secondary" onClick={() => setModalOpen(true)}>Open modal</Button>
                            <Button onClick={() => setConfirmOpen(true)}>Confirm a financial action</Button>
                        </div>
                    </Card>
                    <Modal
                        open={modalOpen}
                        onClose={() => setModalOpen(false)}
                        title="Add a contact"
                        description="Escape, the close button and clicking outside all dismiss this dialog."
                        footer={
                            <>
                                <Button variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button>
                                <Button onClick={() => setModalOpen(false)}>Save contact</Button>
                            </>
                        }
                    >
                        <div className="space-y-4">
                            <Input label="Full name" data-autofocus />
                            <Input label="Phone number" />
                        </div>
                    </Modal>
                    <ConfirmDialog
                        open={confirmOpen}
                        title="Send this campaign?"
                        description="This reserves the amount below from your wallet."
                        details={[
                            { label: 'Recipients', value: '1,200' },
                            { label: 'Amount to reserve', value: formatNaira(552000) },
                        ]}
                        confirmLabel="Reserve and send"
                        onCancel={() => setConfirmOpen(false)}
                        onConfirm={async () => {
                            await new Promise((resolve) => setTimeout(resolve, 900));
                            setConfirmOpen(false);
                            notify({ tone: 'success', title: 'Demo only', description: 'Nothing was sent or charged.' });
                        }}
                    />
                </Section>

                <Section id="states" title="Loading, empty and error states">
                    <div className="grid gap-4 lg:grid-cols-3">
                        <Card title="Loading">
                            <LoadingBlock label="Loading campaigns" />
                            <div className="mt-2 space-y-2"><Skeleton className="h-4 w-full" /><Skeleton className="h-4 w-2/3" /></div>
                            <div className="mt-4 flex items-center gap-2 text-sm text-slate-600"><Spinner /> Inline spinner</div>
                            <div className="mt-4"><Button loading={saving} onClick={() => { setSaving(true); setTimeout(() => setSaving(false), 1500); }}>Try a busy button</Button></div>
                        </Card>
                        <Card title="Empty"><EmptyState title="No contacts yet" description="Add contacts one by one or import a CSV file." action={<Button size="sm">Add contacts</Button>} /></Card>
                        <Card title="Error"><ErrorState description="We could not load your campaigns." onRetry={() => notify({ tone: 'info', title: 'Retrying…' })} /></Card>
                    </div>
                </Section>

                <Section id="table" title="Table with search, filter and pagination">
                    <DataTable
                        caption="Demo campaigns"
                        columns={columns}
                        rows={pageRows}
                        getRowKey={(c) => c.id}
                        loading={loading}
                        error={failed ? { message: 'The demo request failed.', onRetry: () => setFailed(false) } : undefined}
                        search={{ value: search, onChange: (v) => resetTo(() => setSearch(v)), label: 'Search campaigns', placeholder: 'Search campaigns' }}
                        filters={
                            <Select
                                label="Status"
                                value={status}
                                onChange={(e) => resetTo(() => setStatus(e.target.value))}
                                options={[{ value: 'all', label: 'All statuses' }, ...Object.keys(statusTone).map((s) => ({ value: s, label: s }))]}
                            />
                        }
                        pagination={{ meta: { page, perPage: PER_PAGE, total: filtered.length }, onPageChange: setPage }}
                        empty={<EmptyState title="No campaigns match" description="Try a different search or filter." action={<Button size="sm" variant="secondary" onClick={() => resetTo(() => { setSearch(''); setStatus('all'); })}>Clear filters</Button>} />}
                    />
                    <div className="flex flex-wrap gap-3">
                        <Button size="sm" variant="secondary" onClick={() => { setLoading(true); setTimeout(() => setLoading(false), 1500); }}>Show loading state</Button>
                        <Button size="sm" variant="secondary" onClick={() => setFailed(true)}>Show error state</Button>
                    </div>
                </Section>
            </div>
        </BareLayout>
    );
}
