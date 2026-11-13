import { CircleCheck, CircleX, Info, TriangleAlert, X, type LucideIcon } from 'lucide-react';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { cn } from '@/lib/cn';

export type ToastTone = 'success' | 'error' | 'info' | 'warning';

export type ToastInput = {
    tone?: ToastTone;
    title: string;
    description?: string;
    /** Milliseconds before auto-dismiss. Errors stay longer by default. */
    durationMs?: number;
};

type ToastItem = Required<Pick<ToastInput, 'tone' | 'title'>> & { id: number; description?: string };

const MAX_VISIBLE = 4;

const styles: Record<ToastTone, { icon: LucideIcon; bar: string; iconClass: string }> = {
    success: { icon: CircleCheck, bar: 'border-l-success', iconClass: 'text-success' },
    error: { icon: CircleX, bar: 'border-l-danger', iconClass: 'text-danger' },
    info: { icon: Info, bar: 'border-l-navy', iconClass: 'text-navy' },
    warning: { icon: TriangleAlert, bar: 'border-l-orange', iconClass: 'text-orange' },
};

type ToastApi = { notify: (toast: ToastInput) => void; dismiss: (id: number) => void };

const ToastContext = createContext<ToastApi | null>(null);

export function useToast(): ToastApi {
    const ctx = useContext(ToastContext);
    if (!ctx) throw new Error('useToast must be used inside <ToastProvider>');
    return ctx;
}

export function ToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const nextId = useRef(1);
    const timers = useRef(new Map<number, ReturnType<typeof setTimeout>>());

    const dismiss = useCallback((id: number) => {
        const timer = timers.current.get(id);
        if (timer) clearTimeout(timer);
        timers.current.delete(id);
        setToasts((current) => current.filter((t) => t.id !== id));
    }, []);

    const notify = useCallback(
        ({ tone = 'info', title, description, durationMs }: ToastInput) => {
            const id = nextId.current++;
            setToasts((current) => [...current, { id, tone, title, description }].slice(-MAX_VISIBLE));
            const timeout = durationMs ?? (tone === 'error' ? 10000 : 6000);
            if (timeout > 0) timers.current.set(id, setTimeout(() => dismiss(id), timeout));
        },
        [dismiss],
    );

    useEffect(() => {
        const active = timers.current;
        return () => active.forEach((timer) => clearTimeout(timer));
    }, []);

    const api = useMemo(() => ({ notify, dismiss }), [notify, dismiss]);

    return (
        <ToastContext.Provider value={api}>
            {children}
            <div aria-live="polite" className="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:left-auto sm:w-96">
                {toasts.map((toast) => {
                    const style = styles[toast.tone];
                    const Icon = style.icon;
                    return (
                        <div
                            key={toast.id}
                            role={toast.tone === 'error' ? 'alert' : 'status'}
                            className={cn('pointer-events-auto flex w-full gap-3 rounded-md border border-l-4 border-slate-200 bg-white p-4 shadow-lg', style.bar)}
                        >
                            <Icon className={cn('mt-0.5 size-5 shrink-0', style.iconClass)} aria-hidden="true" />
                            <div className="min-w-0 flex-1 text-sm">
                                <p className="font-semibold text-slate-900">{toast.title}</p>
                                {toast.description && <p className="mt-0.5 text-slate-600">{toast.description}</p>}
                            </div>
                            <button type="button" onClick={() => dismiss(toast.id)} aria-label="Dismiss notification" className="h-fit rounded p-1 text-slate-500 hover:bg-slate-100">
                                <X className="size-4" aria-hidden="true" />
                            </button>
                        </div>
                    );
                })}
            </div>
        </ToastContext.Provider>
    );
}
