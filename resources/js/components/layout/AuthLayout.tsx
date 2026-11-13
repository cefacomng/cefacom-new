import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Alert } from '@/components/ui/Feedback';
import type { SharedProps } from '@/types';
import { Logo } from './Logo';

/** Centered layout for sign-in, registration and password screens. */
export function AuthLayout({ title, heading, description, children }: { title: string; heading: string; description?: string; children: ReactNode }) {
    const status = usePage<SharedProps>().props.flash?.status;
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-mist px-4 py-10">
            <Head title={title} />
            <header className="mb-8">
                <a href="/" aria-label="Cefacom home">
                    <Logo tone="dark" />
                </a>
            </header>
            <main className="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h1 className="text-xl font-semibold text-navy">{heading}</h1>
                {description && <p className="mt-1 text-sm text-slate-600">{description}</p>}
                {status && (
                    <div className="mt-4">
                        <Alert tone="success">{status}</Alert>
                    </div>
                )}
                <div className="mt-6">{children}</div>
            </main>
            <footer className="mt-8 text-xs text-slate-600">One platform to reach, notify and convert customers.</footer>
        </div>
    );
}
