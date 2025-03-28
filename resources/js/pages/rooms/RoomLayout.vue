<script lang="ts" setup>
import ControlBar from '@/components/rooms/ComputerControls.vue';
import ComputerGrid from '@/components/rooms/ComputerGrid.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Room, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    room: {
        data: Room;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rooms',
        href: route('rooms.index'),
    },
    {
        title: 'Room Layout',
        href: '#',
    },
];
</script>

<template>
    <Head title="Room Layout" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100svh-4rem)] flex-col gap-4 overflow-hidden p-4">
            <ControlBar />
            <ComputerGrid :room="room" class="flex-1" />
        </div>
    </AppLayout>
</template>
