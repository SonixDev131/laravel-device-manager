<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, Settings, Upload, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get the current user to determine role-based access
const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const isAdmin = computed(() => user.value?.roles?.some((role: any) => role.name === 'super-admin') || false);
const isTeacher = computed(() => user.value?.roles?.some((role: any) => role.name === 'teacher') || false);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        // {
        //     title: 'Dashboard',
        //     href: '/dashboard',
        //     icon: LayoutGrid,
        // },
    ];

    // Add teacher-specific navigation
    if (isTeacher.value) {
        items.push({
            title: 'My Rooms',
            href: '/teacher/my-rooms',
            icon: BookOpen,
        });
    }

    // Add admin/general room access
    // if (isAdmin.value || isTeacher.value) {
    //     items.push({
    //         title: 'Rooms',
    //         href: '/rooms',
    //         icon: Folder,
    //     });
    // }

    // Admin-only items
    if (isAdmin.value) {
        items.push(
            {
                title: 'Teachers',
                href: '/admin/teachers',
                icon: Users,
            },
            {
                title: 'Rooms',
                href: '/admin/rooms',
                icon: Folder,
            },
            {
                title: 'Room Import',
                href: '/admin/room-import',
                icon: Upload,
            },
            {
                title: 'Room Assignments',
                href: '/admin/room-assignments',
                icon: BookOpen,
            },
            {
                title: 'Agents Management',
                href: '/agents',
                icon: Settings,
            },
        );
    }

    return items;
});

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link>
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
