import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { DataTable, type Column } from './DataTable';
import { pageCount } from './Pagination';

type Row = { id: number; name: string; amountKobo: number };
const columns: Array<Column<Row>> = [
    { key: 'name', header: 'Name', cell: (r) => r.name },
    { key: 'amount', header: 'Amount', align: 'right', cell: (r) => String(r.amountKobo) },
];
const rows: Row[] = [{ id: 1, name: 'Alpha', amountKobo: 100 }, { id: 2, name: 'Beta', amountKobo: 250 }];
const base = { caption: 'Test table', columns, getRowKey: (r: Row) => r.id };

describe('DataTable', () => {
    it('renders an accessibly named table with headers and rows', () => {
        render(<DataTable {...base} rows={rows} />);
        const table = screen.getByRole('table', { name: 'Test table' });
        expect(within(table).getAllByRole('columnheader').map((h) => h.textContent)).toEqual(['Name', 'Amount']);
        expect(within(table).getByText('Alpha')).toBeInTheDocument();
        expect(within(table).getByText('Beta')).toBeInTheDocument();
    });

    it('shows skeleton rows, announces loading and hides data while loading', () => {
        render(<DataTable {...base} rows={rows} loading pagination={{ meta: { page: 1, perPage: 10, total: 2 }, onPageChange: () => {} }} />);
        expect(screen.queryByText('Alpha')).not.toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent('Loading');
        expect(screen.queryByRole('navigation', { name: 'Pagination' })).not.toBeInTheDocument();
    });

    it('shows the supplied empty state, or a default one, when there are no rows', () => {
        const { rerender } = render(<DataTable {...base} rows={[]} empty={<p>No campaigns match</p>} />);
        expect(screen.getByText('No campaigns match')).toBeInTheDocument();
        rerender(<DataTable {...base} rows={[]} />);
        expect(screen.getByText('Nothing to show')).toBeInTheDocument();
    });

    it('shows an error state with retry instead of the table', async () => {
        const onRetry = vi.fn();
        render(<DataTable {...base} rows={rows} error={{ message: 'Request failed', onRetry }} />);
        expect(screen.getByRole('alert')).toHaveTextContent('Request failed');
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        await userEvent.click(screen.getByRole('button', { name: 'Try again' }));
        expect(onRetry).toHaveBeenCalledTimes(1);
    });

    it('reports search input changes', async () => {
        const onChange = vi.fn();
        render(<DataTable {...base} rows={rows} search={{ value: '', onChange, label: 'Search campaigns' }} />);
        await userEvent.type(screen.getByRole('searchbox', { name: 'Search campaigns' }), 'a');
        expect(onChange).toHaveBeenCalledWith('a');
    });

    it('paginates: shows the range, disables Previous on page 1 and requests the next page', async () => {
        const onPageChange = vi.fn();
        render(<DataTable {...base} rows={rows} pagination={{ meta: { page: 1, perPage: 8, total: 23 }, onPageChange }} />);
        expect(screen.getByText('Showing 1–8 of 23')).toBeInTheDocument();
        expect(screen.getByText('Page 1 of 3')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
        await userEvent.click(screen.getByRole('button', { name: 'Next' }));
        expect(onPageChange).toHaveBeenCalledWith(2);
    });

    it('makes the horizontally scrollable area a labelled, keyboard-focusable region', () => {
        render(<DataTable {...base} rows={rows} />);
        const region = screen.getByRole('region', { name: 'Test table' });
        expect(region).toHaveAttribute('tabindex', '0');
        expect(region).toContainElement(screen.getByRole('table'));
    });

    it('computes page counts safely', () => {
        expect(pageCount({ page: 1, perPage: 8, total: 0 })).toBe(1);
        expect(pageCount({ page: 1, perPage: 8, total: 8 })).toBe(1);
        expect(pageCount({ page: 1, perPage: 8, total: 9 })).toBe(2);
    });
});
