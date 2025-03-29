<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    CheckSquareIcon,
    ChevronsDownIcon,
    ComputerIcon,
    ImageIcon,
    LockIcon,
    LogOutIcon,
    PowerIcon,
    PowerOffIcon,
    RefreshCwIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

// Define component props
const props = defineProps<{
    // Selected computer IDs
    selectedComputers: string[];
    // Total number of computers available
    totalComputers: number;
}>();

// Define component events
const emit = defineEmits<{
    // Event to clear all selections
    clearSelection: [];
    // Event to select all computers
    selectAll: [];
    // Event to execute a command on selected computers
    executeCommand: [commandType: string];
}>();

// Computed properties
const hasSelectedComputers = computed(() => props.selectedComputers.length > 0);
const selectedCount = computed(() => props.selectedComputers.length);
const hasComputers = computed(() => props.totalComputers > 0);
const allSelected = computed(() => props.selectedComputers.length === props.totalComputers && props.totalComputers > 0);

// Handler functions
const toggleSelectAll = () => {
    if (allSelected.value) {
        emit('clearSelection');
    } else {
        emit('selectAll');
    }
};

const clearSelection = () => {
    emit('clearSelection');
};

const handleExecuteCommand = (commandType: string) => {
    emit('executeCommand', commandType);
};

// Group commands for better organization
const commandGroups = [
    {
        label: 'Power Actions',
        icon: PowerIcon,
        commands: [
            { id: 'power_on', icon: PowerIcon, label: 'Power On' },
            { id: 'power_down', icon: PowerOffIcon, label: 'Power Down' },
            { id: 'reboot', icon: RefreshCwIcon, label: 'Reboot' },
        ],
    },
    {
        label: 'Security',
        icon: LockIcon,
        commands: [
            { id: 'lock', icon: LockIcon, label: 'Lock' },
            { id: 'log_off', icon: LogOutIcon, label: 'Log Off' },
        ],
    },
    {
        label: 'Monitoring',
        icon: ImageIcon,
        commands: [{ id: 'screenshot', icon: ImageIcon, label: 'Screenshot' }],
    },
];
</script>

<template>
    <div class="mb-4 flex flex-col space-y-3 rounded-lg border bg-card p-3 shadow-sm">
        <!-- Computer selection indicator with selection controls -->
        <div class="flex items-center justify-between border-b pb-2">
            <div class="flex items-center gap-2">
                <ComputerIcon class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm font-medium">Computer Controls</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Selection count indicator -->
                <div v-if="hasSelectedComputers" class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                    {{ selectedCount }} selected
                </div>
                <div v-else class="text-xs text-muted-foreground">No computers selected</div>

                <!-- Selection action buttons -->
                <div class="flex items-center">
                    <Button
                        v-if="hasComputers"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        :class="{ 'bg-primary/10': allSelected }"
                        @click="toggleSelectAll"
                        title="Select all computers"
                    >
                        <CheckSquareIcon class="h-4 w-4" :class="allSelected ? 'text-primary' : 'text-muted-foreground'" />
                    </Button>

                    <Button v-if="hasSelectedComputers" variant="ghost" size="icon" class="h-7 w-7" @click="clearSelection" title="Clear selection">
                        <XIcon class="h-4 w-4 text-muted-foreground" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Desktop view - show all dropdown buttons -->
        <div class="hidden flex-wrap gap-2 md:flex">
            <DropdownMenu v-for="group in commandGroups" :key="group.label">
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" :disabled="!hasSelectedComputers" class="flex items-center gap-1">
                        <component :is="group.icon" class="h-4 w-4" />
                        <span>{{ group.label }}</span>
                        <ChevronsDownIcon class="ml-1 h-3 w-3" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="w-48">
                    <DropdownMenuLabel>{{ group.label }}</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="command in group.commands"
                        :key="command.id"
                        :disabled="!hasSelectedComputers"
                        @click="handleExecuteCommand(command.id)"
                    >
                        <component :is="command.icon" class="mr-2 h-4 w-4" />
                        <span>{{ command.label }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Mobile/compact view - more concise UI -->
        <div class="grid grid-cols-3 gap-2 md:hidden">
            <DropdownMenu v-for="group in commandGroups" :key="group.label">
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" :disabled="!hasSelectedComputers" class="w-full">
                        <div class="flex w-full items-center justify-center gap-1">
                            <component :is="group.icon" class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ group.label }}</span>
                            <ChevronsDownIcon class="ml-1 h-3 w-3" />
                        </div>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-48">
                    <DropdownMenuLabel>{{ group.label }}</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="command in group.commands"
                        :key="command.id"
                        :disabled="!hasSelectedComputers"
                        @click="handleExecuteCommand(command.id)"
                    >
                        <component :is="command.icon" class="mr-2 h-4 w-4" />
                        <span>{{ command.label }}</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
