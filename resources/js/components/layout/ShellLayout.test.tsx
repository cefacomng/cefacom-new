import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { SharedProps } from '@/types';

const state: { url: string; props: SharedProps } = { url: '/app', props: {} as SharedProps };

vi.mock('@/components/ui/Toast', () => ({ useToast: () => ({ notify: () => {}, dismiss: () => {} }) }));

vi.mock('@inertiajs/react', () => ({
    router: { post: () => {}, delete: () => {} },
    Head: () => null,
    Link: ({ href, children, ...rest }: { href: string; children: ReactNode }) => <a href={href} {...rest}>{children}</a>,
    usePage: () => ({ url: state.url, props: state.props }),
}));

import { AdminLayout, AppLayout } from './ShellLayout';

const customer = [
    ['overview', 'Overview', '/app'], ['sms', 'SMS', '/app/sms'], ['email', 'Email', '/app/email'], ['contacts', 'Contacts', '/app/contacts'],
    ['reports', 'Reports', '/app/reports'], ['wallet', 'Wallet & Transactions', '/app/wallet'], ['settings', 'Settings', '/app/settings'],
].map(([key, label, href]) => ({ key, label, href, icon: 'users', batch: null }));
const admin = [['overview', 'Overview', '/admin'], ['customers', 'Customers', '/admin/customers']].map(([key, label, href]) => ({ key, label, href, icon: 'users', batch: null }));

beforeEach(() => {
    state.url = '/app';
    state.props = { app: { name: 'Cefacom' }, auth: { user: { name: 'Ada Okafor', email: 'ada@example.com', businessName: null, isStaff: false, emailVerified: true, roles: [] } }, navigation: { customer, admin }, flash: {}, wallet: null };
});

describe('ShellLayout', () => {
    it('renders the seven customer navigation items and marks only the current page', () => {
        state.url = '/app/sms';
        render(<AppLayout title="SMS"><p>content</p></AppLayout>);
        const nav = screen.getByRole('navigation', { hidden: true });
        const links = within(nav).getAllByRole('link', { hidden: true });
        expect(links.map((l) => l.textContent)).toEqual(['Overview', 'SMS', 'Email', 'Contacts', 'Reports', 'Wallet & Transactions', 'Settings']);
        const current = links.filter((l) => l.getAttribute('aria-current') === 'page');
        expect(current).toHaveLength(1);
        expect(current[0]).toHaveTextContent('SMS');
    });

    it('offers a skip link and a main landmark', () => {
        render(<AppLayout title="Overview"><p>content</p></AppLayout>);
        expect(screen.getByRole('link', { name: 'Skip to content' })).toHaveAttribute('href', '#main');
        expect(screen.getByRole('main')).toHaveTextContent('content');
    });

    it('opens and closes the mobile menu with the button, Escape and the close button', async () => {
        render(<AppLayout title="Overview"><p>content</p></AppLayout>);
        const toggle = screen.getByRole('button', { name: 'Open navigation' });
        expect(toggle).toHaveAttribute('aria-expanded', 'false');

        await userEvent.click(toggle);
        expect(toggle).toHaveAttribute('aria-expanded', 'true');
        expect(document.documentElement).toHaveClass('overflow-hidden');

        await userEvent.keyboard('{Escape}');
        expect(toggle).toHaveAttribute('aria-expanded', 'false');
        expect(document.documentElement).not.toHaveClass('overflow-hidden');

        await userEvent.click(toggle);
        await userEvent.click(screen.getByRole('button', { name: 'Close navigation', hidden: true }));
        expect(toggle).toHaveAttribute('aria-expanded', 'false');
    });

    it('shows a neutral dash for the wallet when no balance exists, never an invented amount', () => {
        render(<AppLayout title="Overview"><p>x</p></AppLayout>);
        const chip = screen.getByRole('link', { name: 'Wallet, balance not available yet' });
        expect(chip).toHaveTextContent('—');
    });

    it('shows available and reserved wallet amounts from integer kobo', () => {
        state.props.wallet = { availableKobo: 123450, reservedKobo: 55200 };
        render(<AppLayout title="Overview"><p>x</p></AppLayout>);
        const chip = screen.getByRole('link', { name: 'Wallet, ₦1,234.50 available' });
        expect(chip).toHaveTextContent('₦552.00 reserved');
    });

    it('warns an unverified customer and offers to resend the verification email', () => {
        state.props.auth.user!.emailVerified = false;
        render(<AppLayout title="Overview"><p>x</p></AppLayout>);
        expect(screen.getByText('Verify your email address')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Resend verification email' })).toBeInTheDocument();
    });

    it('does not show the verification warning to verified customers', () => {
        render(<AppLayout title="Overview"><p>x</p></AppLayout>);
        expect(screen.queryByText('Verify your email address')).not.toBeInTheDocument();
    });

    it('gives admin its own navigation and no customer wallet chip', () => {
        state.url = '/admin/customers';
        render(<AdminLayout title="Customers"><p>x</p></AdminLayout>);
        expect(screen.getByRole('navigation', { name: 'Admin navigation', hidden: true })).toBeInTheDocument();
        expect(screen.getByText('Admin console')).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: /Wallet,/ })).not.toBeInTheDocument();
    });
});
