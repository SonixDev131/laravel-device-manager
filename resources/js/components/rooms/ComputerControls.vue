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
import { useComputerStore } from '@/stores/computer';
import { ChevronsUpDownIcon, ComputerIcon, ImageIcon, LockIcon, LogOutIcon, PowerIcon, PowerOffIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed } from 'vue';

// Use the computer store instead of props
const computerStore = useComputerStore();

const hasSelectedComputers = computed(() => computerStore.selectedComputers.length > 0);
const selectedCount = computed(() => computerStore.selectedComputers.length);

const executeCommand = (commandType: string) => {
    computerStore.executeCommand(commandType);
};

// Group commands for better organization
const commandGroups = [
    {
        label: 'Power Actions',
        commands: [
            { id: 'power_on', icon: PowerIcon, label: 'Power On' },
            { id: 'power_down', icon: PowerOffIcon, label: 'Power Down' },
            { id: 'reboot', icon: RefreshCwIcon, label: 'Reboot' },
        ],
    },
    {
        label: 'Security',
        commands: [
            { id: 'lock', icon: LockIcon, label: 'Lock' },
            { id: 'log_off', icon: LogOutIcon, label: 'Log Off' },
        ],
    },
    {
        label: 'Monitoring',
        commands: [{ id: 'screenshot', icon: ImageIcon, label: 'Screenshot' }],
    },
];
</script>

<template>
    <div class="mb-4 flex flex-col space-y-3 rounded-lg border bg-card p-3 shadow-sm">
        <!-- Computer selection indicator -->
        <div class="flex items-center justify-between border-b pb-2">
            <div class="flex items-center gap-2">
                <ComputerIcon class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm font-medium">Computer Controls</span>
            </div>
            <div v-if="hasSelectedComputers" class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                {{ selectedCount }} selected
            </div>
            <div v-else class="text-xs text-muted-foreground">No computers selected</div>
        </div>

        <!-- Desktop view - show all buttons -->
        <div class="hidden flex-wrap gap-2 md:flex">
            <Button
                v-for="group in commandGroups"
                :key="group.label"
                variant="outline"
                size="sm"
                :disabled="!hasSelectedComputers"
                @click="executeCommand(group.commands[0].id)"
                class="group relative"
            >
                <component :is="group.commands[0].icon" class="mr-1 h-4 w-4" />
                {{ group.commands[0].label }}

                <!-- Dropdown for related actions -->
                <DropdownMenu v-if="group.commands.length > 1">
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            :disabled="!hasSelectedComputers"
                            class="absolute right-0 top-0 h-full rounded-l-none border-l opacity-0 focus:opacity-100 group-hover:opacity-100"
                        >
                            <ChevronsUpDownIcon class="h-3 w-3" />
                            <span class="sr-only">More {{ group.label }} options</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuLabel>{{ group.label }}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-for="command in group.commands"
                            :key="command.id"
                            :disabled="!hasSelectedComputers"
                            @click="executeCommand(command.id)"
                        >
                            <component :is="command.icon" class="mr-2 h-4 w-4" />
                            <span>{{ command.label }}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </Button>
        </div>

        <!-- Mobile/compact view - more concise UI -->
        <div class="grid grid-cols-3 gap-2 md:hidden">
            <Button v-for="group in commandGroups" :key="group.label" variant="outline" size="sm" :disabled="!hasSelectedComputers" class="w-full">
                <DropdownMenu>
                    <DropdownMenuTrigger class="flex w-full items-center justify-center gap-1">
                        <component :is="group.commands[0].icon" class="h-4 w-4" />
                        <span class="hidden sm:inline">{{ group.commands[0].label }}</span>
                        <ChevronsUpDownIcon class="ml-1 h-3 w-3" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-48">
                        <DropdownMenuLabel>{{ group.label }}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-for="command in group.commands"
                            :key="command.id"
                            :disabled="!hasSelectedComputers"
                            @click="executeCommand(command.id)"
                        >
                            <component :is="command.icon" class="mr-2 h-4 w-4" />
                            <span>{{ command.label }}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </Button>
        </div>
    </div>
</template>
