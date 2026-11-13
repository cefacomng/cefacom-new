import { Head, Link } from '@inertiajs/react';
import { buttonClasses } from '@/components/ui/Button';

type Props = {
    appName: string;
    designSystem: boolean;
};

export default function Foundation({ appName, designSystem }: Props) {
    return (
        <>
            <Head title="Welcome" />
            <main className="flex min-h-screen items-center justify-center bg-mist px-6">
                <div className="max-w-md text-center">
                    <h1 className="text-4xl font-bold text-navy">{appName}</h1>
                    <p className="mt-3 text-navy/80">One platform to reach, notify and convert customers.</p>
                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                        <Link href="/login" className={buttonClasses('primary')}>Sign in</Link>
                        <Link href="/register" className={buttonClasses('secondary')}>Create account</Link>
                    </div>
                    {designSystem && (
                        <p className="mt-8 text-sm text-slate-600">
                            Development: <a href="/design-system" className="underline">design system</a>
                        </p>
                    )}
                </div>
            </main>
        </>
    );
}
