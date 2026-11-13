import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useToast } from '@/components/ui/Toast';
import type { SharedProps } from '@/types';

/** Shows the one-time status message the server flashed after a redirect as a notification. */
export function useFlashToast(): void {
    const { flash } = usePage<SharedProps>().props;
    const { notify } = useToast();
    const status = flash?.status;

    useEffect(() => {
        if (status) notify({ tone: 'success', title: status });
        // `flash` changes identity on every visit, so repeating the same message still notifies.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash]);
}
