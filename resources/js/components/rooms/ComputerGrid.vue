<script setup lang="ts">
import ComputerDialog from '@/components/rooms/ComputerDialog.vue';
import { useComputerStore } from '@/stores/computer';
import { Room } from '@/types';
import { PlusIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ComputerItem from './ComputerItem.vue';

// Component props - accepts room data that contains the grid dimensions and computers
const props = defineProps<{
    room: {
        data: Room;
    };
}>();

// Use centralized computer store for state management instead of local component state
const computerStore = useComputerStore();

/**
 * Creates a 2D grid representation of the room
 * Each cell contains information about:
 * - Its position (row, col)
 * - Computer at this position (if any)
 * - A linear index for rendering purposes
 */
const gridCells = computed(() => {
    const cells = [];

    for (let row = 1; row <= props.room.data.grid_rows; row++) {
        const rowCells = [];
        for (let col = 1; col <= props.room.data.grid_cols; col++) {
            // Find if a computer exists at this position
            const computer = props.room.data.computers?.find((m) => m.pos_row === row && m.pos_col === col);

            rowCells.push({
                row,
                col,
                computer,
                index: (row - 1) * props.room.data.grid_cols + col, // Calculate linear index for key prop
            });
        }
        cells.push(rowCells);
    }

    return cells;
});

/**
 * Handles computer selection with the following behaviors:
 * - Normal click: Select only the clicked computer
 * - Ctrl+click: Toggle selection of the clicked computer (multi-select)
 * @param id The ID of the computer being clicked
 * @param event Mouse event to check for modifier keys
 */
const handleClick = (computerId: string, event: MouseEvent) => {
    event.stopPropagation(); // Prevent triggering the parent cell click

    // Check if Ctrl key is pressed for multi-selection
    if (event.ctrlKey) {
        // Toggle selection
        if (computerStore.selectedComputers.includes(computerId)) {
            computerStore.selectedComputers = computerStore.selectedComputers.filter((id) => id !== computerId);
        } else {
            computerStore.selectedComputers.push(computerId);
        }
    } else {
        // Replace selection with just this computer
        computerStore.selectedComputers = [computerId];
    }
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
    return `${props.room.data.grid_rows} × ${props.room.data.grid_cols} grid`;
});
</script>

<template>
    <!-- Room container with improved layout and visual structure -->
    <div class="relative flex h-full flex-col overflow-hidden rounded-lg border bg-card shadow-sm">
        <!-- Room information header -->
        <div class="flex items-center justify-between border-b bg-muted/40 px-4 py-2">
            <h3 class="text-sm font-medium text-muted-foreground">
                {{ room.data.name || 'Room Layout' }} <span class="ml-2 text-xs">({{ roomInfo }})</span>
            </h3>
            <span class="text-xs text-muted-foreground">{{ room.data.computers?.length || 0 }} computers</span>
        </div>

        <!-- Scrollable grid container with contained height -->
        <div class="h-[calc(100vh-12rem)] overflow-auto p-4">
            <div class="flex min-h-full items-center justify-center">
                <div
                    class="grid auto-rows-min gap-4 p-2"
                    :style="{
                        gridTemplateColumns: `repeat(${room.data.grid_cols}, minmax(80px, 1fr))`,
                    }"
                >
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
                                :isSelected="computerStore.selectedComputers.includes(cell.computer.id)"
                                @click="handleClick(cell.computer.id, $event)"
                            />

                            <!-- Improved empty cell with "+" button -->
                            <div
                                v-else
                                :key="`empty-${cell.row}-${cell.col}`"
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
                        </template>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal dialog for adding a new computer -->
        <ComputerDialog
            :form-id="computerDialogFormId"
            v-model:is-open="isComputerDialogOpen"
            :position="selectedPosition"
            :room-id="room.data.id"
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
