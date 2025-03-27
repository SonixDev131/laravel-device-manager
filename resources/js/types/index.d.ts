import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Room {
    id: string;
    name: string;
    grid_rows: number;
    grid_cols: number;
    created_at: string;
    updated_at: string;
}

export interface RoomFormData {
    name: string;
    grid_rows: number;
    grid_cols: number;
    type: string;
    status: boolean;
    id?: string;
}

// ...other type definitions...

export type BreadcrumbItemType = BreadcrumbItem;
