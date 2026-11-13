/**
 * Formats an integer amount of kobo as Naira text (e.g. 123450 -> "₦1,234.50").
 * Pure integer arithmetic: money never passes through floating point.
 */
export function formatNaira(kobo: number | bigint): string {
    if (typeof kobo === 'number' && !Number.isSafeInteger(kobo)) {
        throw new RangeError('Amount must be a whole number of kobo');
    }

    const value = BigInt(kobo);
    const negative = value < 0n;
    const abs = negative ? -value : value;
    const naira = (abs / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const remainder = (abs % 100n).toString().padStart(2, '0');

    return `${negative ? '-' : ''}₦${naira}.${remainder}`;
}
