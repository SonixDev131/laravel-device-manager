<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { useCookies } from '@vueuse/integrations/useCookies';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const cookies = useCookies(['sidebar:state']);
</script>

<template>
    <AppShell variant="sidebar">
        <SidebarProvider :defaultOpen="cookies.get('sidebar:state')">
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <slot />
            </AppContent>
        </SidebarProvider>
    </AppShell>
</template>
