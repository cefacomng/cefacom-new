import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout, AppLayout } from '@/components/layout/ShellLayout';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Feedback';
import { Checkbox, Input, PasswordInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';

type Props = {
    area: 'customer' | 'admin';
    basePath: string;
    profile: { name: string; businessName: string | null; phone: string | null; email: string; pendingEmail: string | null; emailVerified: boolean };
    preferences: { low_balance_alerts: boolean; campaign_reports: boolean; product_updates: boolean };
};

export default function Settings({ area, basePath, profile, preferences }: Props) {
    const Layout = area === 'admin' ? AdminLayout : AppLayout;

    const profileForm = useForm({ name: profile.name, business_name: profile.businessName ?? '', phone: profile.phone ?? '' });
    const emailForm = useForm({ email: '', current_password: '' });
    const prefsForm = useForm({ ...preferences });
    const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
    const sessionsForm = useForm({ current_password: '' });

    const submit = (event: FormEvent, action: () => void) => {
        event.preventDefault();
        action();
    };

    return (
        <Layout title="Account settings">
            <PageHeader title="Account settings" description="Your profile, email address, preferences and security." />

            <div className="max-w-2xl space-y-6">
                <Card title="Profile">
                    <form onSubmit={(e) => submit(e, () => profileForm.patch(`${basePath}/profile`, { preserveScroll: true }))} className="space-y-4" noValidate>
                        <Input label="Full name" name="name" autoComplete="name" value={profileForm.data.name} onChange={(e) => profileForm.setData('name', e.target.value)} error={profileForm.errors.name} />
                        <Input label="Business name (optional)" name="business_name" autoComplete="organization" value={profileForm.data.business_name} onChange={(e) => profileForm.setData('business_name', e.target.value)} error={profileForm.errors.business_name} />
                        <Input label="Phone number (optional)" name="phone" type="tel" autoComplete="tel" hint="Nigerian mobile number, for example 0803 123 4567." value={profileForm.data.phone} onChange={(e) => profileForm.setData('phone', e.target.value)} error={profileForm.errors.phone} />
                        <Button type="submit" loading={profileForm.processing}>Save profile</Button>
                    </form>
                </Card>

                <Card title="Email address" description={`Current address: ${profile.email}${profile.emailVerified ? '' : ' (not verified)'}`}>
                    {profile.pendingEmail && (
                        <div className="mb-4">
                            <Alert tone="info" title="Waiting for confirmation">
                                <p>We sent a link to {profile.pendingEmail}. Your address stays the same until you open it.</p>
                                <div className="mt-3 flex gap-2">
                                    <Button size="sm" variant="secondary" onClick={() => router.post(`${basePath}/email/resend`, {}, { preserveScroll: true })}>Resend link</Button>
                                    <Button size="sm" variant="ghost" onClick={() => router.delete(`${basePath}/email`, { preserveScroll: true })}>Cancel change</Button>
                                </div>
                            </Alert>
                        </div>
                    )}
                    <form onSubmit={(e) => submit(e, () => emailForm.post(`${basePath}/email`, { preserveScroll: true, onSuccess: () => emailForm.reset(), onFinish: () => emailForm.reset('current_password') }))} className="space-y-4" noValidate>
                        <Input label="New email address" type="email" name="email" autoComplete="email" value={emailForm.data.email} onChange={(e) => emailForm.setData('email', e.target.value)} error={emailForm.errors.email} />
                        <PasswordInput label="Current password" name="current_password" autoComplete="current-password" hint="Needed to protect your account." value={emailForm.data.current_password} onChange={(e) => emailForm.setData('current_password', e.target.value)} error={emailForm.errors.current_password} />
                        <Button type="submit" loading={emailForm.processing}>Change email address</Button>
                    </form>
                </Card>

                <Card title="Email preferences" description="Choose which emails we may send you. Security emails are always sent.">
                    <form onSubmit={(e) => submit(e, () => prefsForm.patch(`${basePath}/preferences`, { preserveScroll: true }))} className="space-y-4">
                        <Checkbox label="Low wallet balance alerts" checked={prefsForm.data.low_balance_alerts} onChange={(e) => prefsForm.setData('low_balance_alerts', e.target.checked)} />
                        <Checkbox label="Campaign reports" checked={prefsForm.data.campaign_reports} onChange={(e) => prefsForm.setData('campaign_reports', e.target.checked)} />
                        <Checkbox label="Product updates and tips" checked={prefsForm.data.product_updates} onChange={(e) => prefsForm.setData('product_updates', e.target.checked)} />
                        <Button type="submit" loading={prefsForm.processing}>Save preferences</Button>
                    </form>
                </Card>

                <Card title="Password" description="Changing your password signs you out of your other devices.">
                    <form onSubmit={(e) => submit(e, () => passwordForm.put(`${basePath}/password`, { preserveScroll: true, onSuccess: () => passwordForm.reset(), onError: () => passwordForm.reset('password', 'password_confirmation') }))} className="space-y-4" noValidate>
                        <PasswordInput label="Current password" name="current_password" autoComplete="current-password" value={passwordForm.data.current_password} onChange={(e) => passwordForm.setData('current_password', e.target.value)} error={passwordForm.errors.current_password} />
                        <PasswordInput label="New password" name="password" autoComplete="new-password" hint="At least 10 characters, with letters and numbers." value={passwordForm.data.password} onChange={(e) => passwordForm.setData('password', e.target.value)} error={passwordForm.errors.password} />
                        <PasswordInput label="Confirm new password" name="password_confirmation" autoComplete="new-password" value={passwordForm.data.password_confirmation} onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)} error={passwordForm.errors.password_confirmation} />
                        <Button type="submit" loading={passwordForm.processing}>Change password</Button>
                    </form>
                </Card>

                <Card title="Security" description="Sign out of every other browser and device where you are signed in.">
                    <form onSubmit={(e) => submit(e, () => sessionsForm.post(`${basePath}/sessions`, { preserveScroll: true, onFinish: () => sessionsForm.reset() }))} className="space-y-4" noValidate>
                        <PasswordInput label="Current password" name="current_password" autoComplete="current-password" value={sessionsForm.data.current_password} onChange={(e) => sessionsForm.setData('current_password', e.target.value)} error={sessionsForm.errors.current_password} />
                        <Button type="submit" variant="secondary" loading={sessionsForm.processing}>Sign out other devices</Button>
                    </form>
                </Card>
            </div>
        </Layout>
    );
}
