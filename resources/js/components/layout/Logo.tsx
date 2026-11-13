import { cn } from '@/lib/cn';

export function LogoMark({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 32 32" className={cn('size-8', className)} aria-hidden="true">
            <rect width="32" height="32" rx="7" fill="#01b4ba" />
            <path d="M21.5 11.2A7.2 7.2 0 1 0 21.5 20.8" stroke="#01406d" strokeWidth="3.2" fill="none" strokeLinecap="round" />
        </svg>
    );
}

export function Logo({ tone = 'light', name = 'Cefacom' }: { tone?: 'light' | 'dark'; name?: string }) {
    return (
        <span className="inline-flex items-center gap-2.5">
            <LogoMark />
            <span className={cn('text-lg font-semibold tracking-tight', tone === 'light' ? 'text-white' : 'text-navy')}>{name}</span>
        </span>
    );
}
