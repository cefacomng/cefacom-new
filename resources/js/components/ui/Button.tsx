import { LoaderCircle } from 'lucide-react';
import type { ComponentPropsWithoutRef, ReactNode } from 'react';
import { cn } from '@/lib/cn';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type ButtonSize = 'sm' | 'md';

export function buttonClasses(variant: ButtonVariant = 'primary', size: ButtonSize = 'md'): string {
    return cn(
        'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors',
        'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50',
        size === 'sm' ? 'h-8 px-3 text-sm' : 'h-10 px-4 text-sm',
        variant === 'primary' && 'bg-teal-strong text-white hover:bg-teal-strong-hover',
        variant === 'secondary' && 'border border-navy/30 bg-white text-navy hover:bg-slate-50',
        variant === 'ghost' && 'text-navy hover:bg-navy/5',
        variant === 'danger' && 'bg-danger text-white hover:bg-danger-hover',
    );
}

type ButtonProps = ComponentPropsWithoutRef<'button'> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
    /** Shows a spinner, disables the button and announces busy state. Prevents double submission. */
    loading?: boolean;
    icon?: ReactNode;
};

export function Button({ variant = 'primary', size = 'md', loading = false, icon, className, children, disabled, type = 'button', ...rest }: ButtonProps) {
    return (
        <button
            type={type}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={cn(buttonClasses(variant, size), className)}
            {...rest}
        >
            {loading ? <LoaderCircle className="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" /> : icon}
            {children}
        </button>
    );
}
