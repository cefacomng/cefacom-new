import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ConfirmDialog } from './ConfirmDialog';

const details = [{ label: 'Amount to reserve', value: '₦5,520.00' }];

describe('ConfirmDialog', () => {
    it('shows the exact facts being agreed to and starts focus on Cancel', () => {
        render(<ConfirmDialog open title="Send campaign?" details={details} confirmLabel="Reserve and send" onConfirm={() => {}} onCancel={() => {}} />);
        expect(screen.getByText('₦5,520.00')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Cancel' })).toHaveFocus();
    });

    it('runs the confirm action only once even if clicked repeatedly while pending', async () => {
        let finish: () => void = () => {};
        const onConfirm = vi.fn(() => new Promise<void>((resolve) => { finish = resolve; }));
        render(<ConfirmDialog open title="T" confirmLabel="Confirm" onConfirm={onConfirm} onCancel={() => {}} />);

        const confirm = screen.getByRole('button', { name: 'Confirm' });
        await userEvent.click(confirm);
        fireEvent.click(confirm);
        fireEvent.click(confirm);
        expect(onConfirm).toHaveBeenCalledTimes(1);
        expect(screen.getByRole('button', { name: 'Confirm' })).toHaveAttribute('aria-busy', 'true');

        await act(async () => finish());
        expect(screen.getByRole('button', { name: 'Confirm' })).not.toBeDisabled();
    });

    it('cannot be cancelled or dismissed while the action is in flight', async () => {
        let finish: () => void = () => {};
        const onCancel = vi.fn();
        render(<ConfirmDialog open title="T" confirmLabel="Confirm" onConfirm={() => new Promise<void>((r) => { finish = r; })} onCancel={onCancel} />);

        await userEvent.click(screen.getByRole('button', { name: 'Confirm' }));
        expect(screen.getByRole('button', { name: 'Cancel' })).toBeDisabled();
        fireEvent(document.querySelector('dialog') as HTMLDialogElement, new Event('cancel', { cancelable: true }));
        expect(onCancel).not.toHaveBeenCalled();

        await act(async () => finish());
    });

    it('calls onCancel when idle', async () => {
        const onCancel = vi.fn();
        render(<ConfirmDialog open title="T" confirmLabel="Confirm" onConfirm={() => {}} onCancel={onCancel} />);
        await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(onCancel).toHaveBeenCalledTimes(1);
    });
});
