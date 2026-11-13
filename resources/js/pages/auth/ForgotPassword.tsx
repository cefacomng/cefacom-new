import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Field';

export default function ForgotPassword() {
    const form = useForm({ email: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/forgot-password');
    }

    return (
        <AuthLayout title="Reset password" heading="Reset your password" description="Enter your email address and we will send you a link to choose a new password.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <Input label="Email address" type="email" name="email" autoComplete="email" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                <Button type="submit" className="w-full" loading={form.processing}>
                    Send reset link
                </Button>
            </form>
            <p className="mt-5 text-center text-sm">
                <Link href="/login" className="font-medium text-teal-strong underline underline-offset-2">
                    Back to sign in
                </Link>
            </p>
        </AuthLayout>
    );
}
