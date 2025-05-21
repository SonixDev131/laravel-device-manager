<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    CheckSquareIcon,
    ComputerIcon,
    ExternalLinkIcon,
    EyeIcon,
    ImageIcon,
    LockIcon,
    LogOutIcon,
    MessageSquareIcon,
    MonitorIcon,
    PanelLeftIcon,
    PanelRightIcon,
    PlayIcon,
    PowerIcon,
    PowerOffIcon,
    RefreshCwIcon,
    ShieldIcon,
    ShieldOffIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { CommandType } from '@/types/command';

const commandMode = defineModel<'selected' | 'all'>('commandMode', {
    default: 'selected',
});

const props = defineProps<{
    selectedComputers: string[];
    totalComputers: number;
    roomId: string; // Add roomId prop
}>();

const emit = defineEmits<{
    clearSelection: [];
    selectAll: [];
    executeCommand: [command: CommandType];
}>();

const showConfirmation = ref(false);
const pendingCommand = ref<CommandType | null>(null);
const commandDescription = ref('');

watch(
    commandMode,
    (newMode) => {
        // Đảm bảo trạng thái đồng bộ khi commandMode thay đổi từ component cha
        if (newMode === 'all' && props.selectedComputers.length > 0) {
            emit('clearSelection');
        }
    },
    { immediate: true }, // chạy ngay khi component được khởi tạo
);

const selectionText = computed(() => {
    if (commandMode.value === 'all') {
        return 'Broadcasting to all computers';
    }
    return `Selected: ${props.selectedComputers.length} of ${props.totalComputers}`;
});

const isSelectionEmpty = computed(() => {
    return commandMode.value === 'selected' && props.selectedComputers.length === 0;
});

// Map commands to descriptions
const commandDescriptions: Record<CommandType, string> = {
    SHUTDOWN: 'Power down selected computers',
    RESTART: 'Reboot selected computers',
    LOG_OUT: 'Log out users from selected computers',
    LOCK: 'Lock selected computers',
    MESSAGE: 'Send text message to selected computers',
    SCREENSHOT: 'Take screenshot of selected computers',
    UPDATE: 'Update agent on selected computers',
    CUSTOM: 'Run program on selected computers',
    FIREWALL_ON: 'Enable firewall on selected computers',
    FIREWALL_OFF: 'Disable firewall on selected computers',
};

// Handle command with confirmation for critical commands
const handleCommand = (command: CommandType) => {
    const criticalCommands = [CommandType.SHUTDOWN, CommandType.RESTART, CommandType.LOG_OUT, CommandType.FIREWALL_ON, CommandType.FIREWALL_OFF];

    // Update description based on current mode
    const baseDescription = commandDescriptions[command] || 'Execute command';
    commandDescription.value = commandMode.value === 'all' ? baseDescription.replace('selected computers', 'all computers') : baseDescription;

    if (criticalCommands.includes(command)) {
        pendingCommand.value = command;
        showConfirmation.value = true;
    } else {
        emit('executeCommand', command);
    }
};

// Confirm critical command execution
const confirmCommand = () => {
    if (pendingCommand.value) {
        emit('executeCommand', pendingCommand.value);
        pendingCommand.value = null;
        showConfirmation.value = false;
    }
};

// Cancel confirmation
const cancelCommand = () => {
    pendingCommand.value = null;
    showConfirmation.value = false;
};
</script>

<template>
    <div class="z-10 flex flex-col bg-gray-800 shadow-md">
        <!-- Selection controls row styled like Veyon -->
        <div class="flex flex-wrap items-center justify-between gap-4 bg-gray-800 p-2 shadow-sm">
            <div class="flex items-center gap-2">
                <Badge :variant="commandMode === 'all' ? 'default' : 'outline'" class="h-8 text-white transition-colors">
                    <ComputerIcon v-if="commandMode === 'all'" class="mr-1 h-3.5 w-3.5" />
                    {{ selectionText }}
                </Badge>
                <div class="flex min-h-8 items-center">
                    <template v-if="commandMode === 'selected'">
                        <Button size="sm" variant="outline" @click="emit('clearSelection')" class="gap-1">
                            <XIcon class="h-3.5 w-3.5" />
                            Clear
                        </Button>
                        <Button size="sm" variant="outline" @click="emit('selectAll')" class="ml-2 gap-1">
                            <CheckSquareIcon class="h-3.5 w-3.5" />
                            Select All
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Veyon-style selection mode -->
            <RadioGroup v-model:modelValue="commandMode" class="flex gap-4">
                <div class="flex items-center space-x-2">
                    <RadioGroupItem id="selected" value="selected" />
                    <Label for="selected" class="text-white">Manual Selection</Label>
                </div>
                <div class="flex items-center space-x-2">
                    <RadioGroupItem id="all" value="all" />
                    <Label for="all" class="text-white">Broadcast to All</Label>
                </div>
            </RadioGroup>
        </div>

        <!-- Commands section - Veyon-style toolbar -->
        <div>
            <!-- Control bar with all command buttons in a grid -->
            <div class="md:grid-cols-14 grid grid-cols-7 gap-1 border-t border-gray-700 bg-gray-800 p-1 sm:grid-cols-7">
                <!-- Monitoring -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <MonitorIcon class="h-6 w-6 text-lime-500" />
                    <span class="text-xs text-white">Monitoring</span>
                </Button>

                <!-- Fullscreen demo -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <PanelLeftIcon class="h-6 w-6 text-orange-500" />
                    <span class="text-xs text-white">Fullscreen demo</span>
                </Button>

                <!-- Window demo -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <PanelRightIcon class="h-6 w-6 text-purple-500" />
                    <span class="text-xs text-white">Window demo</span>
                </Button>

                <!-- Lock -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.LOCK)"
                >
                    <LockIcon class="h-6 w-6 text-purple-600" />
                    <span class="text-xs text-white">Lock</span>
                </Button>

                <!-- Remote view -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <EyeIcon class="h-6 w-6 text-sky-500" />
                    <span class="text-xs text-white">Remote view</span>
                </Button>

                <!-- Remote control -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <ComputerIcon class="h-6 w-6 text-blue-500" />
                    <span class="text-xs text-white">Remote control</span>
                </Button>

                <!-- Power on -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <PowerIcon class="h-6 w-6 text-green-500" />
                    <span class="text-xs text-white">Power on</span>
                </Button>

                <!-- Reboot -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.RESTART)"
                >
                    <RefreshCwIcon class="h-6 w-6 text-blue-500" />
                    <span class="text-xs text-white">Reboot</span>
                </Button>

                <!-- Power down -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.SHUTDOWN)"
                >
                    <PowerOffIcon class="h-6 w-6 text-red-500" />
                    <span class="text-xs text-white">Power down</span>
                </Button>

                <!-- Logout user -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.LOG_OUT)"
                >
                    <LogOutIcon class="h-6 w-6 text-blue-400" />
                    <span class="text-xs text-white">Logout user</span>
                </Button>

                <!-- Text message -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.MESSAGE)"
                >
                    <MessageSquareIcon class="h-6 w-6 text-blue-400" />
                    <span class="text-xs text-white">Text message</span>
                </Button>

                <!-- Run program -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.CUSTOM)"
                >
                    <PlayIcon class="h-6 w-6 text-gray-300" />
                    <span class="text-xs text-white">Run program</span>
                </Button>

                <!-- Open website -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                >
                    <ExternalLinkIcon class="h-6 w-6 text-blue-400" />
                    <span class="text-xs text-white">Open website</span>
                </Button>

                <!-- Screenshot -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.SCREENSHOT)"
                >
                    <ImageIcon class="h-6 w-6 text-blue-500" />
                    <span class="text-xs text-white">Screenshot</span>
                </Button>

                <!-- Firewall On -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.FIREWALL_ON)"
                >
                    <ShieldIcon class="h-6 w-6 text-green-500" />
                    <span class="text-xs text-white">Firewall On</span>
                </Button>

                <!-- Firewall Off -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.FIREWALL_OFF)"
                >
                    <ShieldOffIcon class="h-6 w-6 text-red-500" />
                    <span class="text-xs text-white">Firewall Off</span>
                </Button>
            </div>
        </div>

        <!-- Veyon-style right-click menu simulation -->
        <div
            v-if="showConfirmation"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm transition-all"
            @click.self="cancelCommand"
        >
            <div class="w-full max-w-sm rounded-lg border bg-gray-800 p-6 text-white shadow-lg animate-in fade-in-50 zoom-in-95">
                <h3 class="text-lg font-semibold">Confirm Action</h3>
                <p class="mt-2 text-gray-300">{{ commandDescription }}</p>

                <div class="mt-4 flex justify-end space-x-2">
                    <Button variant="outline" @click="cancelCommand" class="border-gray-600 text-white hover:bg-gray-700">Cancel</Button>
                    <Button variant="destructive" @click="confirmCommand">Confirm</Button>
                </div>
            </div>
        </div>
    </div>
</template>
