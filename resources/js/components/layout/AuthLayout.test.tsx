import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const page = { props: { flash: {} as { status?: string } } };
vi.mock('@inertiajs/react', () => ({ Head: () => null, usePage: () => page }));

import { AuthLayout } from './AuthLayout';

describe('AuthLayout', () => {
    it('puts all content inside banner, main and contentinfo landmarks', () => {
        render(<AuthLayout title="Sign in" heading="Sign in to Cefacom" description="Use your email."><p>form</p></AuthLayout>);
        expect(screen.getByRole('banner')).toContainElement(screen.getByRole('link', { name: 'Cefacom home' }));
        expect(screen.getByRole('main')).toHaveTextContent('form');
        expect(screen.getByRole('contentinfo')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 1, name: 'Sign in to Cefacom' })).toBeInTheDocument();
    });

    it('shows the one-time status message inline so it cannot be missed', () => {
        page.props.flash = { status: 'Your password has been reset.' };
        render(<AuthLayout title="Sign in" heading="Sign in"><p>form</p></AuthLayout>);
        expect(screen.getByRole('status')).toHaveTextContent('Your password has been reset.');
        page.props.flash = {};
    });
});
