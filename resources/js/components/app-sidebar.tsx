import { Link } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Building2,
    DollarSign,
    FileCheck,
    Folder,
    Landmark,
    Layers,
    LayoutGrid,
    MapPin,
    Percent,
    Receipt,
    Tag,
    UserCheck,
    Users,
    WalletCards,
} from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl, type IsCurrentUrlFn } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

const principalNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
];

const inventarioNavItems: NavItem[] = [
    { title: 'Inventario de Lotes', href: '/inmopro/lots', icon: MapPin },
];

const ventasNavItems: NavItem[] = [
    { title: 'Control Financiero', href: '/inmopro/financial', icon: DollarSign },
    { title: 'Cuentas por cobrar', href: '/inmopro/accounts-receivable', icon: WalletCards },
    { title: 'Comisiones', href: '/inmopro/commissions', icon: Percent },
    { title: 'Clientes', href: '/inmopro/clients', icon: Users },
    { title: 'Vendedores', href: '/inmopro/advisors', icon: UserCheck },
    { title: 'Reportes', href: '/inmopro/reports', icon: BarChart3 },
];

const operacionesNavItems: NavItem[] = [
    { title: 'Tickets de atencion', href: '/inmopro/attention-tickets', icon: FileCheck },
];

const administracionNavItems: NavItem[] = [
    { title: 'Proyectos', href: '/inmopro/projects', icon: Building2 },
    { title: 'Caja y bancos', href: '/inmopro/cash-accounts', icon: Landmark },
    { title: 'Estados de lote', href: '/inmopro/lot-statuses', icon: Tag },
    { title: 'Estados de comision', href: '/inmopro/commission-statuses', icon: Receipt },
    { title: 'Niveles de asesor', href: '/inmopro/advisor-levels', icon: Layers },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

function NavGroup({
    label,
    items,
    isCurrentUrl,
}: {
    label: string;
    items: NavItem[];
    isCurrentUrl: IsCurrentUrlFn;
}) {
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(item.href)}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

export function AppSidebar() {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavGroup label="Principal" items={principalNavItems} isCurrentUrl={isCurrentUrl} />
                <NavGroup label="Inventario" items={inventarioNavItems} isCurrentUrl={isCurrentUrl} />
                <NavGroup label="Ventas" items={ventasNavItems} isCurrentUrl={isCurrentUrl} />
                <NavGroup label="Operaciones" items={operacionesNavItems} isCurrentUrl={isCurrentUrl} />
                <NavGroup label="Administracion" items={administracionNavItems} isCurrentUrl={isCurrentUrl} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
