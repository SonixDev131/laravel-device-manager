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
    computers: Computer[];
    created_at: string;
    updated_at: string;
}

// Computer interfaces and types

export interface Computer {
    id: string;
    hostname: string;
    mac_address: string;
    pos_row: number;
    pos_col: number;
    created_at: string;
    updated_at: string;
    room_id: string;
    status: ComputerStatus;
    last_heartbeat_at: string;
    latest_metric?: SystemMetrics;
}

export interface SystemMetrics {
    cpu_usage: number;
    memory_total: number;
    memory_used: number;
    disk_total: number;
    disk_used: number;
    uptime: number;
    platform: string;
    platform_version: string;
    hostname: string;
    firewall_status: {
        Domain: string;
        Private: string;
        Public: string;
    };
}

export enum ComputerStatus {
    ONLINE = 'online',
    OFFLINE = 'offline',
    SHUTTING_DOWN = 'shutting_down',
    IDLE = 'idle',
    MAINTENANCE = 'maintenance',
}

export enum CommandType {
    LOCK = 'LOCK',
    MESSAGE = 'MESSAGE',
    SCREENSHOT = 'SCREENSHOT',
    SHUTDOWN = 'SHUTDOWN',
    RESTART = 'RESTART',
    LOG_OUT = 'LOG_OUT',
    UPDATE = 'UPDATE',
    CUSTOM = 'CUSTOM',
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
