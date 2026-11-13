import { CircleCheck, CircleX, Inbox, Info, LoaderCircle, TriangleAlert, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { Button } from './Button';

export function Spinner({ className }: { className?: string }) {
    return <LoaderCircle className={cn('size-5 animate-spin text-teal-strong motion-reduce:animate-none', className)} aria-hidden="true" />;
}

export function Skeleton({ className }: { className?: string }) {
    return <div aria-hidden="true" className={cn('animate-pulse rounded bg-slate-200 motion-reduce:animate-none', className)} />;
}

/** Announces a loading state to assistive technology. */
export function LoadingBlock({ label = 'Loading' }: { label?: string }) {
    return (
        <div role="status" className="flex items-center justify-center gap-3 py-10 text-sm text-slate-600">
            <Spinner />
            <span>{label}…</span>
        </div>
    );
}

export type HeadingLevel = 'h2' | 'h3';

export function EmptyState({ title, description, action, icon: Icon = Inbox, headingLevel: Heading = 'h3' }: { title: string; description?: string; action?: ReactNode; icon?: LucideIcon; headingLevel?: HeadingLevel }) {
    return (
        <div className="flex flex-col items-center px-6 py-12 text-center">
            <span className="flex size-11 items-center justify-center rounded-full bg-navy/10 text-navy">
                <Icon className="size-5" aria-hidden="true" />
            </span>
            <Heading className="mt-4 text-base font-semibold text-navy">{title}</Heading>
            {description && <p className="mt-1 max-w-sm text-sm text-slate-600">{description}</p>}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}

export function ErrorState({ title = 'Something went wrong', description, onRetry, headingLevel: Heading = 'h3' }: { title?: string; description?: string; onRetry?: () => void; headingLevel?: HeadingLevel }) {
    return (
        <div role="alert" className="flex flex-col items-center px-6 py-12 text-center">
            <span className="flex size-11 items-center justify-center rounded-full bg-danger-tint text-danger">
                <TriangleAlert className="size-5" aria-hidden="true" />
            </span>
            <Heading className="mt-4 text-base font-semibold text-slate-900">{title}</Heading>
            {description && <p className="mt-1 max-w-sm text-sm text-slate-600">{description}</p>}
            {onRetry && (
                <Button variant="secondary" className="mt-5" onClick={onRetry}>
                    Try again
                </Button>
            )}
        </div>
    );
}

export type AlertTone = 'info' | 'success' | 'warning' | 'danger';

const alertStyles: Record<AlertTone, { box: string; icon: LucideIcon; iconClass: string }> = {
    info: { box: 'border-navy/20 bg-navy/5', icon: Info, iconClass: 'text-navy' },
    success: { box: 'border-success/30 bg-success-tint', icon: CircleCheck, iconClass: 'text-success' },
    warning: { box: 'border-orange/50 bg-orange-tint', icon: TriangleAlert, iconClass: 'text-orange' },
    danger: { box: 'border-danger/30 bg-danger-tint', icon: CircleX, iconClass: 'text-danger' },
};

export function Alert({ tone = 'info', title, children }: { tone?: AlertTone; title?: string; children?: ReactNode }) {
    const style = alertStyles[tone];
    const Icon = style.icon;
    return (
        <div role={tone === 'danger' ? 'alert' : 'status'} className={cn('flex gap-3 rounded-md border px-4 py-3 text-sm', style.box)}>
            <Icon className={cn('mt-0.5 size-4 shrink-0', style.iconClass)} aria-hidden="true" />
            <div className="text-slate-800">
                {title && <p className="font-semibold">{title}</p>}
                {children && <div className={title ? 'mt-0.5' : undefined}>{children}</div>}
            </div>
        </div>
    );
}
