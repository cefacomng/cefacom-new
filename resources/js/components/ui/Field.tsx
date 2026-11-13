import { Eye, EyeOff } from 'lucide-react';
import { useId, useState, type ComponentPropsWithoutRef, type ReactNode } from 'react';
import { cn } from '@/lib/cn';

type FieldChrome = {
    label: string;
    hint?: string;
    error?: string;
};

const controlClasses =
    'block w-full rounded-md border bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 disabled:cursor-not-allowed disabled:bg-slate-100';

function Shell({ id, label, hint, error, children }: FieldChrome & { id: string; children: ReactNode }) {
    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-sm font-medium text-slate-800">
                {label}
            </label>
            {children}
            {hint && !error && (
                <p id={`${id}-hint`} className="text-xs text-slate-500">
                    {hint}
                </p>
            )}
            {error && (
                <p id={`${id}-error`} role="alert" className="text-xs font-medium text-danger">
                    {error}
                </p>
            )}
        </div>
    );
}

function describedBy(id: string, hint?: string, error?: string): string | undefined {
    if (error) return `${id}-error`;
    if (hint) return `${id}-hint`;
    return undefined;
}

export function Input({ label, hint, error, className, id: idProp, ...rest }: FieldChrome & ComponentPropsWithoutRef<'input'>) {
    const generated = useId();
    const id = idProp ?? generated;
    return (
        <Shell id={id} label={label} hint={hint} error={error}>
            <input
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy(id, hint, error)}
                className={cn(controlClasses, 'h-10', error ? 'border-danger' : 'border-slate-300', className)}
                {...rest}
            />
        </Shell>
    );
}

export function Textarea({ label, hint, error, className, id: idProp, rows = 4, ...rest }: FieldChrome & ComponentPropsWithoutRef<'textarea'>) {
    const generated = useId();
    const id = idProp ?? generated;
    return (
        <Shell id={id} label={label} hint={hint} error={error}>
            <textarea
                id={id}
                rows={rows}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy(id, hint, error)}
                className={cn(controlClasses, 'py-2', error ? 'border-danger' : 'border-slate-300', className)}
                {...rest}
            />
        </Shell>
    );
}

type SelectProps = FieldChrome & ComponentPropsWithoutRef<'select'> & { options: Array<{ value: string; label: string }> };

export function Select({ label, hint, error, className, id: idProp, options, ...rest }: SelectProps) {
    const generated = useId();
    const id = idProp ?? generated;
    return (
        <Shell id={id} label={label} hint={hint} error={error}>
            <select
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy(id, hint, error)}
                className={cn(controlClasses, 'h-10', error ? 'border-danger' : 'border-slate-300', className)}
                {...rest}
            >
                {options.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
        </Shell>
    );
}

export function Checkbox({ label, hint, className, id: idProp, ...rest }: Omit<FieldChrome, 'error'> & ComponentPropsWithoutRef<'input'>) {
    const generated = useId();
    const id = idProp ?? generated;
    return (
        <div className="flex items-start gap-3">
            <input
                id={id}
                type="checkbox"
                aria-describedby={hint ? `${id}-hint` : undefined}
                className={cn('mt-0.5 size-4 rounded border-slate-300 accent-teal-strong', className)}
                {...rest}
            />
            <div>
                <label htmlFor={id} className="text-sm font-medium text-slate-800">
                    {label}
                </label>
                {hint && (
                    <p id={`${id}-hint`} className="text-xs text-slate-500">
                        {hint}
                    </p>
                )}
            </div>
        </div>
    );
}

/** Password field with a show/hide control. Hidden by default; the toggle announces its state. */
export function PasswordInput({ label, hint, error, className, id: idProp, ...rest }: FieldChrome & Omit<ComponentPropsWithoutRef<'input'>, 'type'>) {
    const generated = useId();
    const id = idProp ?? generated;
    const [visible, setVisible] = useState(false);
    return (
        <Shell id={id} label={label} hint={hint} error={error}>
            <div className="relative">
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy(id, hint, error)}
                    className={cn(controlClasses, 'h-10 pr-11', error ? 'border-danger' : 'border-slate-300', className)}
                    {...rest}
                />
                <button
                    type="button"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    aria-pressed={visible}
                    className="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-md text-slate-600 hover:text-slate-900"
                >
                    {visible ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
                </button>
            </div>
        </Shell>
    );
}
