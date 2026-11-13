import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ToastProvider, useToast, type ToastInput } from './Toast';

function Harness({ toast }: { toast: ToastInput }) {
    const { notify } = useToast();
    return <button onClick={() => notify(toast)}>fire</button>;
}

afterEach(() => vi.useRealTimers());

describe('Toast', () => {
    it('announces a success notification politely and an error as an alert', async () => {
        const { rerender } = render(<ToastProvider><Harness toast={{ tone: 'success', title: 'Saved' }} /></ToastProvider>);
        await userEvent.click(screen.getByText('fire'));
        expect(screen.getByRole('status')).toHaveTextContent('Saved');

        rerender(<ToastProvider><Harness toast={{ tone: 'error', title: 'Payment failed', description: 'Try again' }} /></ToastProvider>);
        await userEvent.click(screen.getByText('fire'));
        expect(screen.getByRole('alert')).toHaveTextContent('Payment failed');
    });

    it('can be dismissed manually', async () => {
        render(<ToastProvider><Harness toast={{ title: 'Hello' }} /></ToastProvider>);
        await userEvent.click(screen.getByText('fire'));
        await userEvent.click(screen.getByRole('button', { name: 'Dismiss notification' }));
        expect(screen.queryByText('Hello')).not.toBeInTheDocument();
    });

    it('auto-dismisses after its duration', () => {
        vi.useFakeTimers();
        render(<ToastProvider><Harness toast={{ title: 'Temporary', durationMs: 3000 }} /></ToastProvider>);
        act(() => screen.getByText('fire').click());
        expect(screen.getByText('Temporary')).toBeInTheDocument();
        act(() => { vi.advanceTimersByTime(3001); });
        expect(screen.queryByText('Temporary')).not.toBeInTheDocument();
    });

    it('keeps at most four notifications on screen, newest last', () => {
        render(<ToastProvider><Harness toast={{ title: 'Note', durationMs: 0 }} /></ToastProvider>);
        for (let i = 0; i < 6; i++) act(() => screen.getByText('fire').click());
        expect(screen.getAllByText('Note')).toHaveLength(4);
    });

    it('throws a clear error when used outside the provider', () => {
        const spy = vi.spyOn(console, 'error').mockImplementation(() => {});
        expect(() => render(<Harness toast={{ title: 'x' }} />)).toThrow('useToast must be used inside <ToastProvider>');
        spy.mockRestore();
    });
});
