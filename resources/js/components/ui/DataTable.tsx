import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { EmptyState, ErrorState, Skeleton } from './Feedback';
import { Pagination, type PaginationMeta } from './Pagination';
import { SearchInput } from './SearchInput';

export type Column<T> = {
    key: string;
    header: string;
    cell: (row: T) => ReactNode;
    align?: 'left' | 'right';
    className?: string;
};

type DataTableProps<T> = {
    /** Accessible name for the table (announced by screen readers). */
    caption: string;
    columns: Array<Column<T>>;
    rows: T[];
    getRowKey: (row: T) => string | number;
    search?: { value: string; onChange: (value: string) => void; placeholder?: string; label?: string };
    /** Filter controls rendered beside the search box. */
    filters?: ReactNode;
    pagination?: { meta: PaginationMeta; onPageChange: (page: number) => void };
    loading?: boolean;
    error?: { message: string; onRetry?: () => void };
    /** Shown when there are no rows. */
    empty?: ReactNode;
    skeletonRows?: number;
};

export function DataTable<T>({ caption, columns, rows, getRowKey, search, filters, pagination, loading = false, error, empty, skeletonRows = 5 }: DataTableProps<T>) {
    const hasToolbar = Boolean(search || filters);

    return (
        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
            {hasToolbar && (
                <div className="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center">
                    {search && <SearchInput {...search} />}
                    {filters && <div className="flex flex-wrap items-center gap-3">{filters}</div>}
                </div>
            )}

            {error ? (
                <ErrorState description={error.message} onRetry={error.onRetry} />
            ) : (
                <div className="overflow-x-auto" role="region" aria-label={caption} tabIndex={0} aria-busy={loading || undefined}>
                    <table className="w-full min-w-[40rem] text-left text-sm">
                        <caption className="sr-only">{caption}</caption>
                        <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                {columns.map((c) => (
                                    <th key={c.key} scope="col" className={cn('px-4 py-3', c.align === 'right' && 'text-right', c.className)}>
                                        {c.header}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {loading
                                ? Array.from({ length: skeletonRows }, (_, i) => (
                                      <tr key={`skeleton-${i}`}>
                                          {columns.map((c) => (
                                              <td key={c.key} className="px-4 py-3">
                                                  <Skeleton className="h-4 w-full max-w-[10rem]" />
                                              </td>
                                          ))}
                                      </tr>
                                  ))
                                : rows.map((row) => (
                                      <tr key={getRowKey(row)} className="hover:bg-slate-50">
                                          {columns.map((c) => (
                                              <td key={c.key} className={cn('px-4 py-3 text-slate-800', c.align === 'right' && 'text-right tabular-nums', c.className)}>
                                                  {c.cell(row)}
                                              </td>
                                          ))}
                                      </tr>
                                  ))}
                        </tbody>
                    </table>
                    {loading && <p role="status" className="sr-only">Loading</p>}
                    {!loading && rows.length === 0 && (empty ?? <EmptyState title="Nothing to show" description="There are no records to display." />)}
                </div>
            )}

            {pagination && !error && !loading && <Pagination {...pagination} />}
        </div>
    );
}
