import { describe, expect, it } from 'vitest';
import { formatNaira } from './money';

describe('formatNaira', () => {
    it('formats whole and fractional amounts from integer kobo', () => {
        expect(formatNaira(0)).toBe('₦0.00');
        expect(formatNaira(1)).toBe('₦0.01');
        expect(formatNaira(99)).toBe('₦0.99');
        expect(formatNaira(100)).toBe('₦1.00');
        expect(formatNaira(123450)).toBe('₦1,234.50');
        expect(formatNaira(552000)).toBe('₦5,520.00');
    });

    it('groups large amounts and supports bigint beyond the safe integer range', () => {
        expect(formatNaira(123456789012)).toBe('₦1,234,567,890.12');
        expect(formatNaira(9007199254740993n)).toBe('₦90,071,992,547,409.93');
    });

    it('formats negative amounts', () => {
        expect(formatNaira(-5)).toBe('-₦0.05');
        expect(formatNaira(-123450)).toBe('-₦1,234.50');
    });

    it('rejects fractional kobo, NaN and unsafe numbers instead of rounding money', () => {
        expect(() => formatNaira(10.5)).toThrow(RangeError);
        expect(() => formatNaira(Number.NaN)).toThrow(RangeError);
        expect(() => formatNaira(Number.MAX_SAFE_INTEGER + 2)).toThrow(RangeError);
    });
});
