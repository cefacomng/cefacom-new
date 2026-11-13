import { X } from 'lucide-react';
import { useEffect, useId, useRef, type ReactNode } from 'react';
import { cn } from '@/lib/cn';

type ModalProps = {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children?: ReactNode;
    footer?: ReactNode;
    size?: 'sm' | 'md' | 'lg';
};

const sizes = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl' };

/**
 * Accessible modal built on the native <dialog> element: the browser provides the focus trap,
 * inert background and focus return. Escape and backdrop clicks call onClose. Mark an element
 * with data-autofocus to receive initial focus.
 */
export function Modal({ open, onClose, title, description, children, footer, size = 'md' }: ModalProps) {
    const ref = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    const descriptionId = useId();

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;
        if (open && !dialog.open) {
            dialog.showModal();
            dialog.querySelector<HTMLElement>('[data-autofocus]')?.focus();
        }
        if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    useEffect(() => {
        if (!open) return;
        document.documentElement.classList.add('overflow-hidden');
        return () => document.documentElement.classList.remove('overflow-hidden');
    }, [open]);

    return (
        <dialog
            ref={ref}
            aria-labelledby={titleId}
            aria-describedby={description ? descriptionId : undefined}
            onCancel={(event) => {
                event.preventDefault();
                onClose();
            }}
            onClick={(event) => {
                if (event.target === ref.current) onClose();
            }}
            className={cn('m-auto w-[calc(100%-2rem)] rounded-lg bg-white p-0 shadow-xl backdrop:bg-navy-dark/60', sizes[size])}
        >
            {open && (
                <div>
                    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                        <div>
                            <h2 id={titleId} className="text-lg font-semibold text-navy">
                                {title}
                            </h2>
                            {description && (
                                <p id={descriptionId} className="mt-1 text-sm text-slate-600">
                                    {description}
                                </p>
                            )}
                        </div>
                        <button type="button" onClick={onClose} aria-label="Close" className="rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800">
                            <X className="size-5" aria-hidden="true" />
                        </button>
                    </div>
                    {children && <div className="px-6 py-5 text-sm text-slate-700">{children}</div>}
                    {footer && <div className="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">{footer}</div>}
                </div>
            )}
        </dialog>
    );
}
