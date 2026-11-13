import {
    BadgeCheck,
    ChartColumn,
    Globe,
    LayoutDashboard,
    Mail,
    Megaphone,
    MessageSquare,
    Radio,
    ScrollText,
    Send,
    Settings,
    Shield,
    Tag,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';

const icons: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    'message-square': MessageSquare,
    mail: Mail,
    users: Users,
    'bar-chart': ChartColumn,
    wallet: Wallet,
    settings: Settings,
    megaphone: Megaphone,
    tag: Tag,
    radio: Radio,
    send: Send,
    'badge-check': BadgeCheck,
    globe: Globe,
    shield: Shield,
    'scroll-text': ScrollText,
};

export function iconFor(name: string): LucideIcon {
    return icons[name] ?? LayoutDashboard;
}
