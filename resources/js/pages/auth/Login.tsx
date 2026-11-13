import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';
import { Checkbox, Input, PasswordInput } from '@/components/ui/Field';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }

    return (
        <AuthLayout title="Sign in" heading="Sign in to Cefacom" description="Use your email address and password.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <Input label="Email address" type="email" name="email" autoComplete="email" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                <PasswordInput label="Password" name="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} error={form.errors.password} />
                <div className="flex items-center justify-between gap-3">
                    <Checkbox label="Keep me signed in" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                    <Link href="/forgot-password" className="text-sm font-medium text-teal-strong underline underline-offset-2">
                        Forgot password?
                    </Link>
                </div>
                <Button type="submit" className="w-full" loading={form.processing}>
                    Sign in
                </Button>
            </form>
            <p className="mt-5 text-center text-sm text-slate-600">
                New to Cefacom?{' '}
                <Link href="/register" className="font-medium text-teal-strong underline underline-offset-2">
                    Create an account
                </Link>
            </p>
        </AuthLayout>
    );
}
