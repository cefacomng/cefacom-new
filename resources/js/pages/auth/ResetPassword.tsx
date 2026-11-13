import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';
import { Input, PasswordInput } from '@/components/ui/Field';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <AuthLayout title="Choose a new password" heading="Choose a new password" description="You will be signed out everywhere and asked to sign in again.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <Input label="Email address" type="email" name="email" autoComplete="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                <PasswordInput label="New password" name="password" autoComplete="new-password" autoFocus hint="At least 10 characters, with letters and numbers." value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} error={form.errors.password} />
                <PasswordInput label="Confirm new password" name="password_confirmation" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} error={form.errors.password_confirmation} />
                <Button type="submit" className="w-full" loading={form.processing}>
                    Reset password
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
