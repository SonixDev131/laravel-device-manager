<script lang="ts" setup>
import ControlBar from '@/components/rooms/ComputerControls.vue';
import ComputerGrid from '@/components/rooms/ComputerGrid.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Room, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    room: {
        data: Room;
    };
}>();

// State management (moved from store)
const selectedComputers = ref<string[]>([]);

// Computed properties
const totalComputers = computed(() => props.room.data.computers?.length || 0);

// Computer selection methods
const clearSelection = () => {
    selectedComputers.value = [];
};

const selectAllComputers = () => {
    if (!props.room.data.computers?.length) return;
    selectedComputers.value = props.room.data.computers.map((computer) => computer.id);
};

const toggleComputerSelection = (computerId: string) => {
    const index = selectedComputers.value.indexOf(computerId);
    if (index === -1) {
        selectedComputers.value.push(computerId);
    } else {
        selectedComputers.value.splice(index, 1);
    }
};

// Command execution
const executeCommand = (commandType: string) => {
    // Implement command execution logic here
    console.log(`Executing ${commandType} on computers:`, selectedComputers.value);
    // This would typically make an API call to execute the command on the selected computers
    if (selectedComputers.value.length === 0) return;

    if (selectedComputers.value.length === 1) {
        router.post(
            route('rooms.commands.dispatch', {
                room: props.room.data.id,
            }),
            {
                command_type: commandType.toUpperCase(),
                target_type: 'single',
                computer_id: selectedComputers.value[0],
            },
        );
    } else {
        router.post(
            route('rooms.commands.dispatch', {
                room_id: props.room.data.id,
            }),
            {
                command_type: commandType.toUpperCase(),
                target_type: 'group',
                computer_ids: selectedComputers.value,
            },
        );
    }
};

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
            <ControlBar
                :selected-computers="selectedComputers"
                :total-computers="totalComputers"
                @clear-selection="clearSelection"
                @select-all="selectAllComputers"
                @execute-command="executeCommand"
            />
            <ComputerGrid :room="room" :selected-computers="selectedComputers" @toggle-selection="toggleComputerSelection" class="flex-1" />
        </div>
    </AppLayout>
</template>
