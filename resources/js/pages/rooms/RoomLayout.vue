<script lang="ts" setup>
import CommandHistory from '@/components/rooms/CommandHistory.vue';
import ControlBar from '@/components/rooms/ComputerControls.vue';
import ComputerGrid from '@/components/rooms/ComputerGrid.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Computer, Room, type BreadcrumbItem, type CommandType } from '@/types';
import { Head, router, usePoll } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// -> use to polling your server for new information on the current page
usePoll(30000);

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

const props = defineProps<{
    room: {
        data: Room;
    };
}>();

// State management (moved from store)
const selectedComputers = ref<string[]>([]);
// Thêm ref cho chế độ điều khiển
const commandMode = ref<'selected' | 'all'>('selected');
// Computed properties
const totalComputers = computed(() => props.room.data.computers?.length || 0);

// Computer selection methods
const clearSelection = () => {
    selectedComputers.value = [];
};

const selectAllComputers = () => {
    if (!props.room.data.computers?.length) return;
    selectedComputers.value = props.room.data.computers.map((computer: Computer) => computer.id);
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
const executeCommand = (commandType: CommandType) => {
    console.log(`Executing ${commandType} on computers:`, commandMode.value === 'all' ? 'all computers' : selectedComputers.value);

    if (commandMode.value === 'selected') {
        // Kiểm tra nếu không có máy nào được chọn
        if (selectedComputers.value.length === 0) return;

        if (selectedComputers.value.length === 1) {
            router.post(
                route('rooms.commands.publish', {
                    room: props.room.data.id,
                }),
                {
                    command_type: commandType,
                    target_type: 'single',
                    computer_id: selectedComputers.value[0],
                },
            );
        } else {
            router.post(
                route('rooms.commands.publish', {
                    room: props.room.data.id,
                }),
                {
                    command_type: commandType,
                    target_type: 'group',
                    computer_ids: selectedComputers.value,
                },
            );
        }
    } else {
        // Gửi lệnh đến toàn bộ phòng
        router.post(
            route('rooms.commands.publish', {
                room: props.room.data.id,
            }),
            {
                command_type: commandType,
                target_type: 'all',
                // Không cần computer_ids - backend sẽ hiểu là toàn bộ phòng
            },
        );
    }
};
</script>

<template>
    <Head title="Room Layout" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100svh-4rem)] flex-col gap-4 overflow-hidden p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <ControlBar
                    :selected-computers="selectedComputers"
                    :total-computers="totalComputers"
                    :room-id="room.data.id"
                    v-model:commandMode="commandMode"
                    @clear-selection="clearSelection"
                    @select-all="selectAllComputers"
                    @execute-command="executeCommand"
                    class="flex-grow"
                />

                <CommandHistory :room-id="room.data.id" />
            </div>
            <ComputerGrid
                :room="room"
                :selected-computers="selectedComputers"
                :commandMode="commandMode"
                @toggle-selection="toggleComputerSelection"
                class="flex-1"
            />
        </div>
    </AppLayout>
</template>
