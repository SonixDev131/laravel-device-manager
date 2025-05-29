<script lang="ts" setup>
import BlockedWebsites from '@/components/rooms/BlockedWebsites.vue';
import CommandHistory from '@/components/rooms/CommandHistory.vue';
import ControlBar from '@/components/rooms/ComputerControls.vue';
import ComputerGrid from '@/components/rooms/ComputerGrid.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Computer, Room, type BreadcrumbItem } from '@/types';
import { CommandType } from '@/types/command';
import { Head, router, usePoll } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// -> use to polling your server for new information on the current page
usePoll(3000);

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
    room: Room;
}>();

// State management (moved from store)
const selectedComputers = ref<string[]>([]);
// Thêm ref cho chế độ điều khiển
const commandMode = ref<'selected' | 'all'>('selected');
// Ref for ControlBar to access its methods
const controlBarRef = ref<any>(null);
// Computed properties
const totalComputers = computed(() => props.room.computers?.length || 0);

// Computer selection methods
const clearSelection = () => {
    selectedComputers.value = [];
};

const selectAllComputers = () => {
    if (!props.room.computers?.length) return;
    selectedComputers.value = props.room.computers.map((computer: Computer) => computer.id);
};

const toggleComputerSelection = (computerId: string) => {
    const index = selectedComputers.value.indexOf(computerId);
    if (index === -1) {
        selectedComputers.value.push(computerId);
    } else {
        selectedComputers.value.splice(index, 1);
    }
};

// Method to show the block website dialog
const showBlockDialog = (urls?: string[]) => {
    if (controlBarRef.value) {
        controlBarRef.value.showBlockDialog(urls);
    }
};

// Command execution
const executeCommand = (commandType: CommandType, payload?: any) => {
    console.log(`Executing ${commandType} on computers:`, commandMode.value === 'all' ? 'all computers' : selectedComputers.value);

    // Base command data
    const commandData: any = {
        command_type: commandType,
    };

    // Add payload data if provided (for commands like BLOCK_WEBSITE)
    if (payload) {
        commandData.payload = payload;
    }

    if (commandMode.value === 'selected') {
        // Kiểm tra nếu không có máy nào được chọn
        if (selectedComputers.value.length === 0) return;

        if (selectedComputers.value.length === 1) {
            commandData.target_type = 'single';
            commandData.computer_id = selectedComputers.value[0];

            console.log('executeCommand', commandData);
            router.post(
                route('rooms.commands.publish', {
                    room: props.room.id,
                }),
                commandData,
            );
        } else {
            commandData.target_type = 'group';
            commandData.computer_ids = selectedComputers.value;

            router.post(
                route('rooms.commands.publish', {
                    room: props.room.id,
                }),
                commandData,
            );
        }
    } else {
        // Gửi lệnh đến toàn bộ phòng
        commandData.target_type = 'all';

        router.post(
            route('rooms.commands.publish', {
                room: props.room.id,
            }),
            commandData,
        );
    }
};
</script>

<template>
    <Head title="Room Layout" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100svh-4rem)] flex-col overflow-hidden">
            <!-- Full width control bar at top -->
            <ControlBar
                ref="controlBarRef"
                :selected-computers="selectedComputers"
                :total-computers="totalComputers"
                :room-id="room.id"
                v-model:commandMode="commandMode"
                @clear-selection="clearSelection"
                @select-all="selectAllComputers"
                @execute-command="executeCommand"
                @show-block-dialog="showBlockDialog"
                class="w-full"
            />

            <!-- Main content with computer grid -->
            <div class="flex-1 overflow-hidden p-4">
                <ComputerGrid
                    :room="room"
                    :selected-computers="selectedComputers"
                    :commandMode="commandMode"
                    @toggle-selection="toggleComputerSelection"
                    class="h-full"
                />
            </div>

            <!-- Command history button positioned at bottom right -->
            <div class="fixed bottom-6 right-6 z-10 flex flex-col gap-4">
                <BlockedWebsites :room-id="room.id" @block-website="(urls) => showBlockDialog(urls)" />
                <CommandHistory :room-id="room.id" />
            </div>
        </div>
    </AppLayout>
</template>
