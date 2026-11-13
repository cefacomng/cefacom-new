import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { Modal } from './Modal';

function dialogEl(): HTMLDialogElement {
    return document.querySelector('dialog') as HTMLDialogElement;
}

describe('Modal', () => {
    it('renders nothing visible while closed and does not mount its content', () => {
        render(<Modal open={false} onClose={() => {}} title="Add contact"><input aria-label="Name" /></Modal>);
        expect(dialogEl()).not.toHaveAttribute('open');
        expect(screen.queryByLabelText('Name')).not.toBeInTheDocument();
    });

    it('opens as a labelled dialog with its content', () => {
        render(<Modal open onClose={() => {}} title="Add contact" description="Enter details"><input aria-label="Name" /></Modal>);
        expect(dialogEl()).toHaveAttribute('open');
        expect(dialogEl()).toHaveAccessibleName('Add contact');
        expect(dialogEl()).toHaveAccessibleDescription('Enter details');
        expect(screen.getByLabelText('Name')).toBeInTheDocument();
    });

    it('calls onClose for the close button, Escape (cancel event) and backdrop clicks, but not inside clicks', async () => {
        const onClose = vi.fn();
        render(<Modal open onClose={onClose} title="T"><p>Body</p></Modal>);

        await userEvent.click(screen.getByRole('button', { name: 'Close' }));
        expect(onClose).toHaveBeenCalledTimes(1);

        const cancel = new Event('cancel', { cancelable: true });
        fireEvent(dialogEl(), cancel);
        expect(cancel.defaultPrevented).toBe(true);
        expect(onClose).toHaveBeenCalledTimes(2);

        fireEvent.click(dialogEl());
        expect(onClose).toHaveBeenCalledTimes(3);

        await userEvent.click(screen.getByText('Body'));
        expect(onClose).toHaveBeenCalledTimes(3);
    });

    it('moves focus to the data-autofocus element when opened', () => {
        render(<Modal open onClose={() => {}} title="T"><button>First</button><button data-autofocus>Safe choice</button></Modal>);
        expect(screen.getByRole('button', { name: 'Safe choice' })).toHaveFocus();
    });

    it('locks page scroll only while open', () => {
        const { rerender } = render(<Modal open onClose={() => {}} title="T" />);
        expect(document.documentElement).toHaveClass('overflow-hidden');
        rerender(<Modal open={false} onClose={() => {}} title="T" />);
        expect(document.documentElement).not.toHaveClass('overflow-hidden');
    });
});
