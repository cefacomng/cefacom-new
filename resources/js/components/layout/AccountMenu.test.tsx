import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { describe, expect, it, vi } from 'vitest';
import type { AuthUser } from '@/types';

const post = vi.fn();
vi.mock('@inertiajs/react', () => ({
    router: { post: (...args: unknown[]) => post(...args) },
    Link: ({ href, children, ...rest }: { href: string; children: ReactNode }) => <a href={href} {...rest}>{children}</a>,
}));

import { AccountMenu } from './AccountMenu';

const user: AuthUser = { name: 'Ada Okafor', email: 'ada@example.com', businessName: null, isStaff: false, emailVerified: true, roles: [] };

describe('AccountMenu', () => {
    it('is closed by default and opens with the person\'s name and email', async () => {
        render(<AccountMenu user={user} settingsHref="/app/settings" />);
        const button = screen.getByRole('button', { name: 'Account menu for Ada Okafor' });
        expect(button).toHaveAttribute('aria-expanded', 'false');
        expect(screen.queryByText('ada@example.com')).not.toBeInTheDocument();

        await userEvent.click(button);
        expect(button).toHaveAttribute('aria-expanded', 'true');
        expect(screen.getByText('ada@example.com')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Account settings' })).toHaveAttribute('href', '/app/settings');
    });

    it('closes on Escape and returns focus to the button', async () => {
        render(<AccountMenu user={user} settingsHref="/app/settings" />);
        const button = screen.getByRole('button', { name: /Account menu/ });
        await userEvent.click(button);
        await userEvent.keyboard('{Escape}');
        expect(button).toHaveAttribute('aria-expanded', 'false');
        expect(button).toHaveFocus();
    });

    it('closes when clicking elsewhere', async () => {
        render(<div><AccountMenu user={user} settingsHref="/app/settings" /><p>outside</p></div>);
        await userEvent.click(screen.getByRole('button', { name: /Account menu/ }));
        await userEvent.click(screen.getByText('outside'));
        expect(screen.queryByText('ada@example.com')).not.toBeInTheDocument();
    });

    it('signs out with a POST request, never a link', async () => {
        render(<AccountMenu user={user} settingsHref="/app/settings" />);
        await userEvent.click(screen.getByRole('button', { name: /Account menu/ }));
        await userEvent.click(screen.getByRole('button', { name: 'Sign out' }));
        expect(post).toHaveBeenCalledWith('/logout');
    });

    it('shows staff role names', async () => {
        render(<AccountMenu user={{ ...user, isStaff: true, roles: ['Super Admin'] }} settingsHref="/admin/account" />);
        await userEvent.click(screen.getByRole('button', { name: /Account menu/ }));
        expect(screen.getByText('Super Admin')).toBeInTheDocument();
    });
});
