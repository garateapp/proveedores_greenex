import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type NavSection, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ClipboardList,
    Clock,
    CreditCard,
    FileCheck,
    FileText,
    LayoutGrid,
    MapPin,
    Settings,
    UploadCloud,
    Users,
    Wrench,
} from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth?.user?.isAdmin ?? false;

    const dashboardItem: NavItem = {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    };

    const configuracionItems: NavItem[] = isAdmin
        ? [
              {
                  title: 'Tipos de Faena',
                  href: '/tipo-faenas',
                  icon: MapPin,
              },
              {
                  title: 'Faenas',
                  href: '/faenas',
                  icon: MapPin,
              },
              {
                  title: 'Tipos de Documentos',
                  href: '/tipo-documentos',
                  icon: FileText,
              },
              {
                  title: 'Plantillas Firma',
                  href: '/admin/plantillas-documentos-trabajador',
                  icon: FileText,
              },
              {
                  title: 'Contratistas',
                  href: '/admin/contratistas',
                  icon: Building2,
              },
              {
                  title: 'Usuarios',
                  href: '/admin/users',
                  icon: Users,
              },
              {
                  title: 'Ubicaciones',
                  href: '/admin/ubicaciones',
                  icon: MapPin,
              },
              {
                  title: 'Tarjetas QR',
                  href: '/admin/packing/tarjetas',
                  icon: Clock,
              },
              {
                  title: 'Auditoría',
                  href: '/admin/audit-logs',
                  icon: ClipboardList,
              },
          ]
        : [
              {
                  title: 'Faenas',
                  href: '/faenas',
                  icon: MapPin,
              },
          ];

    const contratistasItems: NavItem[] = [
        {
            title: 'Personal',
            href: '/trabajadores',
            icon: Users,
        },
        {
            title: 'Estados de Pago',
            href: '/estados-pago',
            icon: CreditCard,
        },
        {
            title: 'Asistencia',
            href: '/asistencias',
            icon: Clock,
        },
        {
            title: 'Marcaciones Packing',
            href: '/packing/marcaciones',
            icon: ClipboardList,
        },
    ];

    const documentacionItems: NavItem[] = [
        {
            title: 'Centro de Carga',
            href: '/centro-carga',
            icon: UploadCloud,
        },
        {
            title: 'Centro Contratistas',
            href: '/centro-carga-contratistas',
            icon: UploadCloud,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Aprobaciones',
                      href: '/documentos/aprobaciones',
                      icon: FileCheck,
                  },
              ]
            : []),
    ];

    const herramientasItems: NavItem[] = [
        {
            title: 'Cuadratura asistencia',
            href: '/herramientas/cuadratura-asistencia',
            icon: Wrench,
        },
    ];

    const navSections: NavSection[] = [
        {
            title: 'Configuración',
            icon: Settings,
            items: configuracionItems,
            defaultOpen: true,
        },
        {
            title: 'Contratistas',
            icon: Building2,
            items: contratistasItems,
            defaultOpen: true,
        },
        {
            title: 'Documentación',
            icon: FileText,
            items: documentacionItems,
            defaultOpen: true,
        },
        {
            title: 'Herramientas',
            icon: Wrench,
            items: herramientasItems,
            defaultOpen: true,
        },
    ].filter((section) => section.items.length > 0);

    return (
        <Sidebar collapsible="icon" variant="inset" className="portal-sidebar">
            <SidebarHeader className="portal-sidebar-section border-b border-sidebar-border/70 px-3 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-12">
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="portal-sidebar-section px-1">
                <NavMain primaryItem={dashboardItem} sections={navSections} />
            </SidebarContent>

            <SidebarFooter className="portal-sidebar-section border-t border-sidebar-border/70">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
