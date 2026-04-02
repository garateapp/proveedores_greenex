import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export type UserRole = 'admin' | 'contratista' | 'supervisor';

export interface Contratista {
    id: number;
    rut: string;
    razon_social: string;
    nombre_fantasia: string | null;
    estado: 'activo' | 'inactivo' | 'bloqueado';
}

export interface Auth {
    user: User | null;
    contratista: Contratista | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface NavSection {
    title: string;
    icon?: LucideIcon | null;
    items: NavItem[];
    defaultOpen?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    roleLabel: string;
    contratista_id: number | null;
    is_active: boolean;
    isAdmin: boolean;
    isContratista: boolean;
    isSupervisor: boolean;
    canManageContratistas: boolean;
    canManageWorkers: boolean;
    canViewAllData: boolean;
    avatar?: string;
    email_verified_at?: string | null;
    two_factor_enabled?: boolean;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown; // This allows for additional properties...
}
