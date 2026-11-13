import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from './Button';

export type PaginationMeta = { page: number; perPage: number; total: number };

export function pageCount(meta: PaginationMeta): number {
    return Math.max(1, Math.ceil(meta.total / meta.perPage));
}

export function Pagination({ meta, onPageChange }: { meta: PaginationMeta; onPageChange: (page: number) => void }) {
    const pages = pageCount(meta);
    const start = meta.total === 0 ? 0 : (meta.page - 1) * meta.perPage + 1;
    const end = Math.min(meta.page * meta.perPage, meta.total);

    return (
        <nav aria-label="Pagination" className="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm sm:flex-row">
            <p className="text-slate-600" aria-live="polite">
                {meta.total === 0 ? 'No results' : `Showing ${start}–${end} of ${meta.total}`}
            </p>
            <div className="flex items-center gap-2">
                <Button size="sm" variant="secondary" icon={<ChevronLeft className="size-4" aria-hidden="true" />} disabled={meta.page <= 1} onClick={() => onPageChange(meta.page - 1)}>
                    Previous
                </Button>
                <span className="px-1 text-slate-600">
                    Page {meta.page} of {pages}
                </span>
                <Button size="sm" variant="secondary" disabled={meta.page >= pages} onClick={() => onPageChange(meta.page + 1)}>
                    Next
                    <ChevronRight className="size-4" aria-hidden="true" />
                </Button>
            </div>
        </nav>
    );
}
