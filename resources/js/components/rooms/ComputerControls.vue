<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    CheckSquareIcon,
    ChevronsDownIcon,
    ComputerIcon,
    DownloadIcon,
    ImageIcon,
    LockIcon,
    LogOutIcon,
    MessageSquareIcon,
    PowerIcon,
    PowerOffIcon,
    RefreshCwIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const commandMode = defineModel<string>('commandMode');

const props = defineProps<{
    selectedComputers: string[];
    totalComputers: number;
}>();

const emit = defineEmits<{
    clearSelection: [];
    selectAll: [];
    executeCommand: [command: CommandType];
}>();

// Define valid command types
type CommandType = 'shutdown' | 'restart' | 'logout' | 'lock' | 'message' | 'screenshot' | 'update';

const localCommandMode = ref(commandMode.value || 'selected');
const showConfirmation = ref(false);
const pendingCommand = ref<CommandType | null>(null);
const commandDescription = ref('');

// Watch for changes to command mode and emit the update
const updateCommandMode = (mode: 'selected' | 'all') => {
    // Khi chuyển sang broadcast mode, tự động clear selection để tránh nhầm lẫn UI
    if (mode === 'all' && props.selectedComputers.length > 0) {
        emit('clearSelection');
    }

    localCommandMode.value = mode;
    emit('update:commandMode', mode);
};

// Watch for prop changes from parent
watch(
    () => commandMode,
    (newMode) => {
        if (newMode !== undefined) {
            localCommandMode.value = newMode;

            // Đảm bảo trạng thái đồng bộ khi commandMode thay đổi từ component cha
            if (newMode === 'all' && props.selectedComputers.length > 0) {
                emit('clearSelection');
            }
        }
    },
);

const selectionText = computed(() => {
    if (localCommandMode.value === 'all') {
        return 'Broadcasting to all computers';
    }
    return `Selected: ${props.selectedComputers.length} of ${props.totalComputers}`;
});

const isSelectionEmpty = computed(() => {
    return localCommandMode.value === 'selected' && props.selectedComputers.length === 0;
});

// Map commands to descriptions
const commandDescriptions: Record<CommandType, string> = {
    shutdown: 'Shutdown selected computers',
    restart: 'Restart selected computers',
    logout: 'Log out users from selected computers',
    lock: 'Lock selected computers',
    message: 'Send message to selected computers',
    screenshot: 'Take screenshot of selected computers',
    update: 'Update agent on selected computers',
};

// Handle command with confirmation for critical commands
const handleCommand = (command: CommandType) => {
    const criticalCommands = ['shutdown', 'restart', 'logout'];

    // Update description based on current mode
    const baseDescription = commandDescriptions[command] || 'Execute command';
    commandDescription.value = localCommandMode.value === 'all' ? baseDescription.replace('selected computers', 'all computers') : baseDescription;

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

// Only expose one executeCommand method
defineExpose({
    executeCommand: (command: CommandType) => handleCommand(command),
});
</script>

<template>
    <div class="flex flex-col gap-4 rounded-md border border-border bg-card p-4 shadow-sm">
        <!-- Selection controls row with consistent height -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <Badge :variant="localCommandMode === 'all' ? 'default' : 'outline'" class="h-8 transition-colors">
                    <ComputerIcon v-if="localCommandMode === 'all'" class="mr-1 h-3.5 w-3.5" />
                    {{ selectionText }}
                </Badge>
                <div class="flex min-h-8 items-center">
                    <template v-if="localCommandMode === 'selected'">
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

            <RadioGroup v-model="localCommandMode" class="flex gap-4" @update:modelValue="updateCommandMode">
                <div class="flex items-center space-x-2">
                    <RadioGroupItem id="selected" value="selected" />
                    <Label for="selected">Manual Selection</Label>
                </div>
                <div class="flex items-center space-x-2">
                    <RadioGroupItem id="all" value="all" />
                    <Label for="all">Broadcast to All</Label>
                </div>
            </RadioGroup>
        </div>

        <!-- Commands section -->
        <div class="space-y-4">
            <!-- Quick access common commands with consistent button height -->
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <Button class="h-10 flex-1 justify-start" variant="outline" :disabled="isSelectionEmpty" @click="handleCommand('lock')">
                    <LockIcon class="mr-2 h-4 w-4" />
                    Lock
                </Button>

                <Button class="h-10 flex-1 justify-start" variant="outline" :disabled="isSelectionEmpty" @click="handleCommand('message')">
                    <MessageSquareIcon class="mr-2 h-4 w-4" />
                    Message
                </Button>

                <Button class="h-10 flex-1 justify-start" variant="outline" :disabled="isSelectionEmpty" @click="handleCommand('screenshot')">
                    <ImageIcon class="mr-2 h-4 w-4" />
                    Screenshot
                </Button>

                <!-- Power controls dropdown with consistent height -->
                <DropdownMenu>
                    <DropdownMenuTrigger as="div">
                        <Button class="h-10 w-full flex-1 justify-start" variant="outline" :disabled="isSelectionEmpty">
                            <PowerIcon class="mr-2 h-4 w-4" />
                            Power
                            <ChevronsDownIcon class="ml-auto h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuItem @click="handleCommand('shutdown')" class="text-destructive">
                            <PowerOffIcon class="mr-2 h-4 w-4" />
                            Shutdown
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="handleCommand('restart')">
                            <RefreshCwIcon class="mr-2 h-4 w-4" />
                            Restart
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="handleCommand('logout')">
                            <LogOutIcon class="mr-2 h-4 w-4" />
                            Logout
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Maintenance commands -->
                <Button class="h-10 flex-1 justify-start" variant="outline" :disabled="isSelectionEmpty" @click="handleCommand('update')">
                    <DownloadIcon class="mr-2 h-4 w-4" />
                    Update
                </Button>
            </div>
        </div>

        <!-- Confirmation dialog with backdrop blur for better focus -->
        <div
            v-if="showConfirmation"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm transition-all"
            @click.self="cancelCommand"
        >
            <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg animate-in fade-in-50 zoom-in-95">
                <h3 class="text-lg font-semibold">Confirm Action</h3>
                <p class="mt-2 text-muted-foreground">{{ commandDescription }}</p>

                <div class="mt-4 flex justify-end space-x-2">
                    <Button variant="outline" @click="cancelCommand">Cancel</Button>
                    <Button variant="destructive" @click="confirmCommand">Confirm</Button>
                </div>
            </div>
        </div>
    </div>
</template>
