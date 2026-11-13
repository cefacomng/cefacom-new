import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';

export default function VerifyEmail({ email }: { email: string }) {
    const form = useForm({});

    function resend(event: FormEvent) {
        event.preventDefault();
        form.post('/email/verification-notification');
    }

    return (
        <AuthLayout title="Verify your email" heading="Check your email" description={`We sent a verification link to ${email}. Open it to unlock sending and your wallet.`}>
            <form onSubmit={resend} className="space-y-3">
                <Button type="submit" className="w-full" loading={form.processing}>
                    Resend verification email
                </Button>
            </form>
            <p className="mt-4 text-center text-sm text-slate-600">
                Wrong address? Sign out and register again, or change it later in account settings.
            </p>
            <div className="mt-5 flex justify-center">
                <Button variant="ghost" onClick={() => router.post('/logout')}>
                    Sign out
                </Button>
            </div>
        </AuthLayout>
    );
}
