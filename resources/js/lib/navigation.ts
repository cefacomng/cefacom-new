import type { NavItem } from '@/types';

/** Overview matches exactly; other sections match their own page and any page beneath them. */
export function isNavActive(url: string, item: Pick<NavItem, 'href' | 'key'>): boolean {
    const path = url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/';
    if (item.key === 'overview') {
        return path === item.href;
    }
    return path === item.href || path.startsWith(`${item.href}/`);
}
