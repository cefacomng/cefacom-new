import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { PasswordInput } from './Field';

describe('PasswordInput', () => {
    it('hides the password by default and toggles visibility with an announced state', async () => {
        render(<PasswordInput label="Password" />);
        const input = screen.getByLabelText('Password');
        expect(input).toHaveAttribute('type', 'password');

        const toggle = screen.getByRole('button', { name: 'Show password' });
        expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await userEvent.click(toggle);
        expect(input).toHaveAttribute('type', 'text');
        expect(screen.getByRole('button', { name: 'Hide password' })).toHaveAttribute('aria-pressed', 'true');
        await userEvent.click(screen.getByRole('button', { name: 'Hide password' }));
        expect(input).toHaveAttribute('type', 'password');
    });

    it('never submits a surrounding form when the toggle is pressed', () => {
        render(<PasswordInput label="Password" />);
        expect(screen.getByRole('button', { name: 'Show password' })).toHaveAttribute('type', 'button');
    });

    it('wires errors and hints for assistive technology', () => {
        render(<PasswordInput label="Password" hint="At least 10 characters." error="Too short." />);
        const input = screen.getByLabelText('Password');
        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAccessibleDescription('Too short.');
    });
});
