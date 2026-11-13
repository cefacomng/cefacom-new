import { Search } from 'lucide-react';

export function SearchInput({ value, onChange, label = 'Search', placeholder = 'Search…' }: { value: string; onChange: (value: string) => void; label?: string; placeholder?: string }) {
    return (
        <div className="relative w-full sm:max-w-xs">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
                type="search"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                aria-label={label}
                placeholder={placeholder}
                className="block h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400"
            />
        </div>
    );
}
