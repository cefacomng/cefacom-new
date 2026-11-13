import { useRef, useState } from 'react';
import { Button } from './Button';
import { Modal } from './Modal';

type ConfirmDialogProps = {
    open: boolean;
    title: string;
    description?: string;
    /** Exact facts the person is agreeing to (for example the amount and the number of recipients). */
    details?: Array<{ label: string; value: string }>;
    confirmLabel: string;
    cancelLabel?: string;
    tone?: 'default' | 'danger';
    onConfirm: () => void | Promise<void>;
    onCancel: () => void;
};

/**
 * Confirmation for consequential and financial actions. Focus starts on Cancel (the safe choice),
 * the confirm action can only run once while pending, and the dialog cannot be dismissed mid-action.
 */
export function ConfirmDialog({ open, title, description, details, confirmLabel, cancelLabel = 'Cancel', tone = 'default', onConfirm, onCancel }: ConfirmDialogProps) {
    const [pending, setPending] = useState(false);
    const inFlight = useRef(false);

    async function confirm() {
        if (inFlight.current) return;
        inFlight.current = true;
        setPending(true);
        try {
            await onConfirm();
        } finally {
            inFlight.current = false;
            setPending(false);
        }
    }

    return (
        <Modal
            open={open}
            onClose={() => {
                if (!pending) onCancel();
            }}
            title={title}
            description={description}
            size="sm"
            footer={
                <>
                    <Button variant="secondary" onClick={onCancel} disabled={pending} data-autofocus>
                        {cancelLabel}
                    </Button>
                    <Button variant={tone === 'danger' ? 'danger' : 'primary'} onClick={confirm} loading={pending}>
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {details && details.length > 0 && (
                <dl className="divide-y divide-slate-200 rounded-md border border-slate-200">
                    {details.map((d) => (
                        <div key={d.label} className="flex justify-between gap-4 px-3 py-2">
                            <dt className="text-slate-600">{d.label}</dt>
                            <dd className="font-semibold tabular-nums text-slate-900">{d.value}</dd>
                        </div>
                    ))}
                </dl>
            )}
        </Modal>
    );
}
