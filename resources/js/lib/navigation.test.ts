import { describe, expect, it } from 'vitest';
import { isNavActive } from './navigation';

const overview = { key: 'overview', href: '/app' };
const sms = { key: 'sms', href: '/app/sms' };

describe('isNavActive', () => {
    it('matches overview only on its exact path', () => {
        expect(isNavActive('/app', overview)).toBe(true);
        expect(isNavActive('/app/', overview)).toBe(true);
        expect(isNavActive('/app?tab=1', overview)).toBe(true);
        expect(isNavActive('/app/sms', overview)).toBe(false);
    });

    it('matches a section and pages beneath it, but not lookalike prefixes', () => {
        expect(isNavActive('/app/sms', sms)).toBe(true);
        expect(isNavActive('/app/sms/campaigns/3', sms)).toBe(true);
        expect(isNavActive('/app/smsx', sms)).toBe(false);
        expect(isNavActive('/app/email', sms)).toBe(false);
    });
});
