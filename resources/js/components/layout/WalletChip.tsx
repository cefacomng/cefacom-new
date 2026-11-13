import { Link } from '@inertiajs/react';
import { Wallet as WalletIcon } from 'lucide-react';
import { formatNaira } from '@/lib/money';
import type { Wallet } from '@/types';

/** Always-visible wallet balance. `wallet` is null until Batch 4 provides real balances. */
export function WalletChip({ wallet, href = '/app/wallet' }: { wallet: Wallet | null; href?: string }) {
    const available = wallet ? formatNaira(wallet.availableKobo) : null;
    const label = available ? `Wallet, ${available} available` : 'Wallet, balance not available yet';

    return (
        <Link href={href} aria-label={label} className="flex h-9 items-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm hover:bg-slate-50">
            <WalletIcon className="size-4 text-teal-strong" aria-hidden="true" />
            <span className="hidden text-xs text-slate-600 sm:inline">Wallet</span>
            <span className="font-semibold tabular-nums text-navy">{available ?? '—'}</span>
            {wallet && wallet.reservedKobo > 0 && <span className="hidden text-xs text-slate-600 md:inline">· {formatNaira(wallet.reservedKobo)} reserved</span>}
        </Link>
    );
}
