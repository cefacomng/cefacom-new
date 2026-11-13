import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { Button } from './Button';

describe('Button', () => {
    it('defaults to type=button so it never submits a form by accident', () => {
        render(<Button>Save</Button>);
        expect(screen.getByRole('button', { name: 'Save' })).toHaveAttribute('type', 'button');
    });

    it('is disabled and busy while loading, and ignores clicks', async () => {
        const onClick = vi.fn();
        render(<Button loading onClick={onClick}>Saving</Button>);
        const button = screen.getByRole('button', { name: 'Saving' });
        expect(button).toBeDisabled();
        expect(button).toHaveAttribute('aria-busy', 'true');
        await userEvent.click(button);
        expect(onClick).not.toHaveBeenCalled();
    });

    it('calls onClick when enabled', async () => {
        const onClick = vi.fn();
        render(<Button onClick={onClick}>Go</Button>);
        await userEvent.click(screen.getByRole('button', { name: 'Go' }));
        expect(onClick).toHaveBeenCalledTimes(1);
    });
});
