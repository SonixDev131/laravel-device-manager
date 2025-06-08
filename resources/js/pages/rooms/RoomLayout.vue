<script lang="ts" setup>
import BlockedWebsites from '@/components/rooms/BlockedWebsites.vue';
import CommandHistory from '@/components/rooms/CommandHistory.vue';
import ControlBar from '@/components/rooms/ComputerControls.vue';
import ComputerGrid from '@/components/rooms/ComputerGrid.vue';
import DeleteComputerDialog from '@/components/rooms/DeleteComputerDialog.vue';
import EditComputerDialog from '@/components/rooms/EditComputerDialog.vue';
import ScreenshotGallery from '@/components/rooms/ScreenshotGallery.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Computer, Room, type BreadcrumbItem } from '@/types';
import { CommandType } from '@/types/command';
import { Head, router, usePoll } from '@inertiajs/vue3';
import { ComputerIcon, ImageIcon } from 'lucide-vue-next';
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
    userAccess?: {
        can_send_commands?: boolean;
        can_take_screenshots?: boolean;
        can_block_websites?: boolean;
        can_manage_computers?: boolean;
    };
}>();

// State management (moved from store)
const selectedComputers = ref<string[]>([]);
// Thêm ref cho chế độ điều khiển
const commandMode = ref<'selected' | 'all'>('selected');
// Ref for ControlBar to access its methods
const controlBarRef = ref<any>(null);
// Active tab state
const activeTab = ref<'computers' | 'screenshots'>('computers');

// Edit/Delete Computer Dialog State
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedComputerForEdit = ref<Computer | null>(null);
const selectedComputerForDelete = ref<Computer | null>(null);

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

// Edit/Delete Computer handlers
const handleEditComputer = (computer: Computer) => {
    selectedComputerForEdit.value = computer;
    showEditDialog.value = true;
};

const handleDeleteComputer = (computer: Computer) => {
    selectedComputerForDelete.value = computer;
    showDeleteDialog.value = true;
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

    // If it's a screenshot command, switch to screenshots tab after a delay
    if (commandType === CommandType.SCREENSHOT) {
        setTimeout(() => {
            activeTab.value = 'screenshots';
        }, 1000);
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

            <!-- Tabs with navigation and content -->
            <div class="flex-1 overflow-hidden">
                <Tabs :default-value="activeTab" class="flex h-full flex-col" @update:modelValue="activeTab = $event as 'computers' | 'screenshots'">
                    <!-- Navigation Tabs -->
                    <div class="border-b px-4 py-3">
                        <TabsList class="grid w-full max-w-md grid-cols-2">
                            <TabsTrigger value="computers" class="flex items-center gap-2">
                                <ComputerIcon class="h-4 w-4" />
                                Computers
                                <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs">
                                    {{ totalComputers }}
                                </span>
                            </TabsTrigger>
                            <TabsTrigger value="screenshots" class="flex items-center gap-2">
                                <ImageIcon class="h-4 w-4" />
                                Screenshots
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-1 overflow-hidden">
                        <!-- Computer Grid Tab -->
                        <TabsContent value="computers" class="m-0 h-full p-4">
                            <ComputerGrid
                                :room="room"
                                :selected-computers="selectedComputers"
                                :commandMode="commandMode"
                                :user-access="userAccess"
                                @toggle-selection="toggleComputerSelection"
                                @editComputer="handleEditComputer"
                                @deleteComputer="handleDeleteComputer"
                                class="h-full"
                            />
                        </TabsContent>

                        <!-- Screenshots Tab -->
                        <TabsContent value="screenshots" class="m-0 h-full overflow-auto p-4">
                            <ScreenshotGallery :room-id="room.id" />
                        </TabsContent>
                    </div>
                </Tabs>
            </div>

            <!-- Command history and blocked websites positioned at bottom right -->
            <div class="fixed bottom-6 right-6 z-10 flex flex-col gap-4">
                <BlockedWebsites :room-id="room.id" @block-website="(urls) => showBlockDialog(urls)" />
                <CommandHistory :room-id="room.id" />
            </div>
        </div>

        <!-- Edit Computer Dialog -->
        <EditComputerDialog v-model:isOpen="showEditDialog" :computer="selectedComputerForEdit" :room-id="room.id" form-id="edit-computer-form" />

        <!-- Delete Computer Dialog -->
        <DeleteComputerDialog v-model:isOpen="showDeleteDialog" :computer="selectedComputerForDelete" :room-id="room.id" />
    </AppLayout>
</template>
