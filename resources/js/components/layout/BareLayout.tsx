import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Logo } from './Logo';

/** Simple page chrome without the application navigation (developer reference pages). */
export function BareLayout({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="min-h-screen">
            <Head title={title} />
            <header className="bg-navy">
                <div className="mx-auto flex h-14 max-w-5xl items-center px-4">
                    <a href="/" aria-label="Cefacom home">
                        <Logo />
                    </a>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-8">{children}</main>
        </div>
    );
}
