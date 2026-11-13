import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AuthLayout } from '@/components/layout/AuthLayout';
import { Button } from '@/components/ui/Button';
import { Input, PasswordInput } from '@/components/ui/Field';

export default function Register() {
    const form = useForm({ name: '', business_name: '', email: '', password: '', password_confirmation: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <AuthLayout title="Create account" heading="Create your Cefacom account" description="Reach your customers by SMS and email, and pay only for what you send.">
            <form onSubmit={submit} className="space-y-4" noValidate>
                <Input label="Full name" name="name" autoComplete="name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
                <Input label="Business name (optional)" name="business_name" autoComplete="organization" value={form.data.business_name} onChange={(e) => form.setData('business_name', e.target.value)} error={form.errors.business_name} />
                <Input label="Email address" type="email" name="email" autoComplete="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                <PasswordInput label="Password" name="password" autoComplete="new-password" hint="At least 10 characters, with letters and numbers." value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} error={form.errors.password} />
                <PasswordInput label="Confirm password" name="password_confirmation" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} error={form.errors.password_confirmation} />
                <Button type="submit" className="w-full" loading={form.processing}>
                    Create account
                </Button>
            </form>
            <p className="mt-5 text-center text-sm text-slate-600">
                Already have an account?{' '}
                <Link href="/login" className="font-medium text-teal-strong underline underline-offset-2">
                    Sign in
                </Link>
            </p>
        </AuthLayout>
    );
}
