export type NavItem = {
    key: string;
    label: string;
    href: string;
    icon: string;
    batch: number | null;
};

export type Wallet = {
    availableKobo: number;
    reservedKobo: number;
};

export type AuthUser = {
    name: string;
    email: string;
    businessName: string | null;
    isStaff: boolean;
    emailVerified: boolean;
    roles: string[];
};

export type SharedProps = {
    app: { name: string };
    auth: { user: AuthUser | null };
    navigation: { customer?: NavItem[]; admin?: NavItem[] } | null;
    flash: { status?: string | null };
    wallet: Wallet | null;
    [key: string]: unknown;
};
