<script setup lang="ts">
import ComputerDialog from '@/components/rooms/ComputerDialog.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Room } from '@/types';
import { PlusIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ComputerItem from './ComputerItem.vue';

const props = defineProps<{
    room: Room;
    selectedComputers: string[];
    commandMode: 'selected' | 'all';
    userAccess?: {
        can_manage_computers?: boolean;
    };
}>();

const emit = defineEmits<{
    toggleSelection: [computerId: string];
}>();

/**
 * Creates a 2D grid representation of the room
 * Each cell contains information about:
 * - Its position (row, col)
 * - Computer at this position (if any)
 * - A linear index for rendering purposes
 */
const gridCells = computed(() => {
    const cells = [];

    for (let row = 1; row <= props.room.grid_rows; row++) {
        const rowCells = [];
        for (let col = 1; col <= props.room.grid_cols; col++) {
            // Find if a computer exists at this position
            const computer = props.room.computers?.find((m) => m.pos_row === row && m.pos_col === col);

            rowCells.push({
                row,
                col,
                computer,
                index: (row - 1) * props.room.grid_cols + col, // Calculate linear index for key prop
            });
        }
        cells.push(rowCells);
    }

    return cells;
});

/**
 * Handles a click on a computer item to toggle its selection
 * @param computerId The ID of the computer to toggle
 */
const handleToggleSelection = (computerId: string) => {
    emit('toggleSelection', computerId);
};

// State for the computer creation dialog
const isComputerDialogOpen = ref(false);
const selectedPosition = ref({ row: 1, col: 1 }); // Default position
const computerDialogFormId = 'add-computer-form';

/**
 * Opens the add computer dialog with the selected grid position
 * @param row Row position in the grid
 * @param col Column position in the grid
 */
const handleAddComputer = (row: number, col: number) => {
    selectedPosition.value = { row, col };
    isComputerDialogOpen.value = true;
};

/**
 * Closes the computer creation dialog
 */
const closeComputerDialog = () => {
    isComputerDialogOpen.value = false;
};

// Display room dimensions info
const roomInfo = computed(() => {
    return `${props.room.grid_rows} × ${props.room.grid_cols} grid`;
});
</script>

<template>
    <!-- Room container with improved layout and visual structure -->
    <div class="relative flex h-full flex-col overflow-hidden rounded-lg border bg-card shadow-sm">
        <!-- Room information header -->
        <div class="flex items-center justify-between border-b bg-muted/40 px-4 py-2">
            <h3 class="text-sm font-medium text-muted-foreground">
                {{ room.name || 'Room Layout' }} <span class="ml-2 text-xs">({{ roomInfo }})</span>
            </h3>
            <span class="text-xs text-muted-foreground">{{ room.computers?.length || 0 }} computers</span>
        </div>

        <!-- Scrollable grid container with contained height -->
        <ScrollArea class="grid h-[calc(100vh-12rem)] place-content-center p-4">
            <div class="flex min-h-full items-center justify-center">
                <div class="grid auto-rows-min gap-6" :style="{ gridTemplateColumns: `repeat(${room.grid_cols}, minmax(80px, 1fr))` }">
                    <!-- Iterate through each row in our computed grid -->
                    <template v-for="row in gridCells" :key="row[0].row">
                        <!-- Iterate through each cell in the current row -->
                        <template v-for="cell in row" :key="cell.index">
                            <!-- Display computer component if a computer exists at this position -->
                            <ComputerItem
                                v-if="cell.computer"
                                :key="`computer-${cell.row}-${cell.col}`"
                                :index="cell.index"
                                :computer="cell.computer"
                                :isSelected="selectedComputers.includes(cell.computer.id)"
                                :isSelectable="commandMode === 'selected'"
                                @click="commandMode === 'selected' ? handleToggleSelection(cell.computer.id) : null"
                            />

                            <!-- Improved empty cell with "+" button and additional height for consistency -->
                            <div v-else :key="`empty-${cell.row}-${cell.col}`" class="flex flex-col items-center">
                                <!-- Show add button only if user has permission -->
                                <div
                                    v-if="userAccess?.can_manage_computers"
                                    class="group relative flex h-16 w-16 cursor-pointer select-none items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/20 bg-background/50 transition-all hover:border-muted-foreground/50 hover:bg-muted/20"
                                    @click="handleAddComputer(cell.row, cell.col)"
                                >
                                    <div
                                        class="absolute flex h-full w-full items-center justify-center opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <PlusIcon class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div class="text-xs text-muted-foreground/50 group-hover:opacity-0">{{ cell.row }},{{ cell.col }}</div>
                                </div>
                                <!-- Show empty placeholder if user doesn't have permission -->
                                <div
                                    v-else
                                    class="flex h-16 w-16 items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/10 bg-background/20"
                                >
                                    <div class="text-xs text-muted-foreground/30">{{ cell.row }},{{ cell.col }}</div>
                                </div>
                                <!-- Empty space matching the height of the status indicator -->
                                <div class="h-6"></div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </ScrollArea>

        <!-- Modal dialog for adding a new computer -->
        <ComputerDialog
            :form-id="computerDialogFormId"
            v-model:is-open="isComputerDialogOpen"
            :position="selectedPosition"
            :room-id="room.id"
            @close="closeComputerDialog"
        />
    </div>
</template>

<style scoped>
/* Add subtle animation for hover effects */
.group {
    transition: all 0.2s ease-in-out;
}
</style>
