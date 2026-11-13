import { Link, router } from '@inertiajs/react';
import { ChevronDown, LogOut, Settings } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { AuthUser } from '@/types';

/**
 * Disclosure menu for the signed-in person: account settings and sign out.
 * Closes on Escape, outside click and after choosing an item; focus returns to the button.
 */
export function AccountMenu({ user, settingsHref }: { user: AuthUser; settingsHref: string }) {
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);
    const button = useRef<HTMLButtonElement>(null);
    const panelId = useId();

    useEffect(() => {
        if (!open) return;
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                button.current?.focus();
            }
        };
        const onPointer = (event: MouseEvent) => {
            if (root.current && !root.current.contains(event.target as Node)) setOpen(false);
        };
        document.addEventListener('keydown', onKey);
        document.addEventListener('mousedown', onPointer);
        return () => {
            document.removeEventListener('keydown', onKey);
            document.removeEventListener('mousedown', onPointer);
        };
    }, [open]);

    const initial = (user.name.trim()[0] ?? '?').toUpperCase();

    return (
        <div ref={root} className="relative">
            <button
                ref={button}
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-expanded={open}
                aria-controls={panelId}
                aria-label={`Account menu for ${user.name}`}
                className="flex items-center gap-2 rounded-md p-1 hover:bg-slate-100"
            >
                <span className="flex size-8 items-center justify-center rounded-full bg-navy text-sm font-semibold text-white" aria-hidden="true">
                    {initial}
                </span>
                <ChevronDown className="size-4 text-slate-600" aria-hidden="true" />
            </button>
            {open && (
                <div id={panelId} className="absolute right-0 z-30 mt-2 w-64 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                    <div className="border-b border-slate-200 px-4 py-3">
                        <p className="truncate text-sm font-semibold text-slate-900">{user.name}</p>
                        <p className="truncate text-xs text-slate-600">{user.email}</p>
                        {user.roles.length > 0 && <p className="mt-1 text-xs text-slate-600">{user.roles.join(', ')}</p>}
                    </div>
                    <Link href={settingsHref} onClick={() => setOpen(false)} className="flex items-center gap-2 px-4 py-2 text-sm text-slate-800 hover:bg-slate-50">
                        <Settings className="size-4" aria-hidden="true" />
                        Account settings
                    </Link>
                    <button
                        type="button"
                        onClick={() => {
                            setOpen(false);
                            router.post('/logout');
                        }}
                        className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-800 hover:bg-slate-50"
                    >
                        <LogOut className="size-4" aria-hidden="true" />
                        Sign out
                    </button>
                </div>
            )}
        </div>
    );
}
