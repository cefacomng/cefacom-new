import { Head, Link, router, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { iconFor } from '@/lib/icons';
import { useFlashToast } from '@/lib/useFlash';
import { isNavActive } from '@/lib/navigation';
import { Alert } from '@/components/ui/Feedback';
import { Button } from '@/components/ui/Button';
import type { SharedProps } from '@/types';
import { AccountMenu } from './AccountMenu';
import { Logo } from './Logo';
import { WalletChip } from './WalletChip';

type ShellLayoutProps = {
    area: 'customer' | 'admin';
    title: string;
    children: ReactNode;
};

/**
 * Application shell: fixed sidebar on large screens, off-canvas menu on small screens.
 * The same component serves the customer and admin areas; admin is visually distinct and separate.
 */
export function ShellLayout({ area, title, children }: ShellLayoutProps) {
    const { props, url } = usePage<SharedProps>();
    const items = props.navigation?.[area] ?? [];
    const admin = area === 'admin';
    const user = props.auth?.user ?? null;
    useFlashToast();
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => setMenuOpen(false), [url]);

    useEffect(() => {
        if (!menuOpen) return;
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setMenuOpen(false);
        };
        document.addEventListener('keydown', onKey);
        document.documentElement.classList.add('overflow-hidden');
        return () => {
            document.removeEventListener('keydown', onKey);
            document.documentElement.classList.remove('overflow-hidden');
        };
    }, [menuOpen]);

    useEffect(() => {
        const query = window.matchMedia?.('(min-width: 1024px)');
        if (!query) return;
        const onChange = (event: MediaQueryListEvent) => event.matches && setMenuOpen(false);
        query.addEventListener('change', onChange);
        return () => query.removeEventListener('change', onChange);
    }, []);

    const appName = props.app?.name ?? 'Cefacom';
    const home = admin ? '/admin' : '/app';

    return (
        <div className="min-h-screen">
            <Head title={`${title} · ${admin ? 'Admin' : appName}`} />
            <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-navy">
                Skip to content
            </a>

            {menuOpen && <div className="fixed inset-0 z-30 bg-navy-dark/60 lg:hidden" onClick={() => setMenuOpen(false)} aria-hidden="true" />}

            <aside
                id="primary-navigation"
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col text-white transition-transform lg:w-64',
                    admin ? 'bg-navy-dark' : 'bg-navy',
                    !menuOpen && 'max-lg:invisible max-lg:-translate-x-full',
                )}
            >
                <div className="flex h-14 items-center justify-between px-4">
                    <Link href={home} aria-label={`${appName} home`}>
                        <Logo />
                    </Link>
                    <button type="button" onClick={() => setMenuOpen(false)} aria-label="Close navigation" className="rounded-md p-1.5 text-white/80 hover:bg-white/10 lg:hidden">
                        <X className="size-5" aria-hidden="true" />
                    </button>
                </div>
                {admin && <p className="mx-4 mb-2 w-fit rounded border border-teal/60 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-teal">Admin console</p>}

                <nav aria-label={admin ? 'Admin navigation' : 'Main navigation'} className="flex-1 overflow-y-auto px-3 py-2">
                    <ul className="space-y-1">
                        {items.map((item) => {
                            const Icon = iconFor(item.icon);
                            const active = isNavActive(url, item);
                            return (
                                <li key={item.key}>
                                    <Link
                                        href={item.href}
                                        aria-current={active ? 'page' : undefined}
                                        className={cn(
                                            'flex items-center gap-3 rounded-md border-l-4 px-3 py-2 text-sm font-medium',
                                            active ? 'border-teal bg-white/10 text-white' : 'border-transparent text-white/80 hover:bg-white/5 hover:text-white',
                                        )}
                                    >
                                        <Icon className="size-4 shrink-0" aria-hidden="true" />
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                {user && <p className="truncate border-t border-white/10 px-4 py-3 text-xs text-white/70">{user.email}</p>}
            </aside>

            <div className="lg:pl-64" inert={menuOpen}>
                <header className="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:px-8">
                    <button
                        type="button"
                        onClick={() => setMenuOpen(true)}
                        aria-label="Open navigation"
                        aria-expanded={menuOpen}
                        aria-controls="primary-navigation"
                        className="rounded-md p-1.5 text-navy hover:bg-navy/5 lg:hidden"
                    >
                        <Menu className="size-5" aria-hidden="true" />
                    </button>
                    {admin && <span className="text-sm font-semibold text-navy lg:hidden">Admin</span>}
                    <div className="ml-auto flex items-center gap-3">
                        {!admin && <WalletChip wallet={props.wallet} />}
                        {user && <AccountMenu user={user} settingsHref={admin ? '/admin/account' : '/app/settings'} />}
                    </div>
                </header>

                <main id="main" tabIndex={-1} className="mx-auto max-w-7xl px-4 py-6 lg:px-8">
                    {user && !user.emailVerified && (
                        <div className="mb-6">
                            <Alert tone="warning" title="Verify your email address">
                                <p>Sending messages and managing your wallet unlock once your email address is verified.</p>
                                <Button size="sm" variant="secondary" className="mt-3" onClick={() => router.post('/email/verification-notification', {}, { preserveScroll: true })}>
                                    Resend verification email
                                </Button>
                            </Alert>
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}

export function AppLayout(props: Omit<ShellLayoutProps, 'area'>) {
    return <ShellLayout area="customer" {...props} />;
}

export function AdminLayout(props: Omit<ShellLayoutProps, 'area'>) {
    return <ShellLayout area="admin" {...props} />;
}
