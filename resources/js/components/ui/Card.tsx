import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

export function Card({ title, description, actions, children, className }: { title?: string; description?: string; actions?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <section className={cn('rounded-lg border border-slate-200 bg-white', className)}>
            {(title || actions) && (
                <header className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        {title && <h2 className="text-base font-semibold text-navy">{title}</h2>}
                        {description && <p className="mt-0.5 text-sm text-slate-600">{description}</p>}
                    </div>
                    {actions}
                </header>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}

/** A single figure with its label. `value` null renders a neutral dash (no data yet), never an invented number. */
export function StatTile({ label, value, note }: { label: string; value: string | null; note?: string }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p className="text-xs font-medium text-slate-600">{label}</p>
            <p className="mt-1 text-2xl font-semibold tabular-nums text-navy">{value ?? '—'}</p>
            {note && <p className="mt-0.5 text-xs text-slate-500">{note}</p>}
        </div>
    );
}
