<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import {
    CheckSquareIcon,
    ComputerIcon,
    DownloadIcon,
    ExternalLinkIcon,
    EyeIcon,
    GlobeIcon,
    ImageIcon,
    LockIcon,
    LogOutIcon,
    MessageSquareIcon,
    MonitorIcon,
    PanelLeftIcon,
    PanelRightIcon,
    PowerIcon,
    PowerOffIcon,
    RefreshCwIcon,
    ShieldIcon,
    ShieldOffIcon,
    TerminalIcon,
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
    executeCommand: [command: CommandType, payload?: any];
    showBlockDialog: [urls?: string[]];
}>();

const showConfirmation = ref(false);
const pendingCommand = ref<CommandType | null>(null);
const commandDescription = ref('');

// Website blocking
const showBlockWebsiteDialog = ref(false);
const websiteToBlock = ref('');
const websiteError = ref('');

// Raw Command Dialog
const showRawCommandDialog = ref(false);
const commandName = ref('');
const commandArgs = ref('');

// Function not available dialog
const showNotAvailableDialog = ref(false);

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
    BLOCK_WEBSITE: 'Block website on selected computers',
};

// Handle command with confirmation for critical commands
const handleCommand = (command: CommandType) => {
    const criticalCommands = [CommandType.SHUTDOWN, CommandType.RESTART, CommandType.LOG_OUT, CommandType.FIREWALL_ON, CommandType.FIREWALL_OFF];

    // Special handling for website blocking
    if (command === CommandType.BLOCK_WEBSITE) {
        showBlockWebsiteDialog.value = true;
        return;
    }

    // Special handling for custom/raw commands
    if (command === CommandType.CUSTOM) {
        showRawCommandDialog.value = true;
        return;
    }

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

// Website blocking methods
const validateWebsiteUrl = (url: string): boolean => {
    // Simple validation for URL format
    // URL must be non-empty and a valid format
    if (!url.trim()) {
        websiteError.value = 'Website URL is required';
        return false;
    }

    // Split by comma and trim each entry for multiple websites
    const websites = url.split(',').map((site) => site.trim());
    const urlPattern = /^([a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+)(\/[a-zA-Z0-9-._~:/?#[\]@!$&'()*+,;=]*)?$/;

    for (const site of websites) {
        if (!site) continue; // Skip empty entries

        // Test without http/https prefix
        if (!urlPattern.test(site)) {
            websiteError.value = `Invalid website format: ${site}`;
            return false;
        }
    }

    websiteError.value = '';
    return true;
};

const submitBlockWebsite = () => {
    if (validateWebsiteUrl(websiteToBlock.value)) {
        // Process comma-separated list of websites
        const websites = websiteToBlock.value
            .split(',')
            .map((site) => site.trim())
            .filter((site) => site); // Filter out empty entries

        // Execute the command with the current commandMode
        // (commandMode will be 'all' when initiated from the sidebar)
        emit('executeCommand', CommandType.BLOCK_WEBSITE, { urls: websites });
        websiteToBlock.value = '';
        showBlockWebsiteDialog.value = false;
    }
};

const cancelBlockWebsite = () => {
    websiteToBlock.value = '';
    websiteError.value = '';
    showBlockWebsiteDialog.value = false;
};

// Method to be called from parent to show the block website dialog with pre-filled websites
const showBlockDialog = (urls?: string[]) => {
    if (urls && urls.length > 0) {
        websiteToBlock.value = urls.join(', ');
    }
    showBlockWebsiteDialog.value = true;
};

// Raw command methods
const submitRawCommand = () => {
    if (!commandName.value.trim()) return;

    const payload = {
        name: commandName.value.trim(),
        args: commandArgs.value.trim() || null,
    };

    emit('executeCommand', CommandType.CUSTOM, payload);
    console.log('executeCommand', CommandType.CUSTOM, payload);

    // Reset form and close dialog
    commandName.value = '';
    commandArgs.value = '';
    showRawCommandDialog.value = false;
};

const cancelRawCommand = () => {
    commandName.value = '';
    commandArgs.value = '';
    showRawCommandDialog.value = false;
};

// Show not available dialog
const showNotAvailable = () => {
    showNotAvailableDialog.value = true;
};

// Close not available dialog
const closeNotAvailableDialog = () => {
    showNotAvailableDialog.value = false;
};

// Expose method to parent component
defineExpose({
    showBlockDialog,
});
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
                    @click="showNotAvailable"
                >
                    <MonitorIcon class="h-6 w-6 text-lime-500" />
                    <span class="text-xs text-white">Monitoring</span>
                </Button>

                <!-- Fullscreen demo -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="showNotAvailable"
                >
                    <PanelLeftIcon class="h-6 w-6 text-orange-500" />
                    <span class="text-xs text-white">Fullscreen demo</span>
                </Button>

                <!-- Window demo -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="showNotAvailable"
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
                    @click="showNotAvailable"
                >
                    <EyeIcon class="h-6 w-6 text-sky-500" />
                    <span class="text-xs text-white">Remote view</span>
                </Button>

                <!-- Remote control -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="showNotAvailable"
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
                    <TerminalIcon class="h-6 w-6 text-purple-500" />
                    <span class="text-xs text-white">Raw Command</span>
                </Button>

                <!-- Open website -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="showNotAvailable"
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

                <!-- Update Agent -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.UPDATE)"
                >
                    <DownloadIcon class="h-6 w-6 text-cyan-500" />
                    <span class="text-xs text-white">Update Agent</span>
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

                <!-- Block Website -->
                <Button
                    class="h-16 flex-1 flex-col items-center justify-center gap-1 transition-colors hover:bg-gray-700"
                    variant="ghost"
                    :disabled="isSelectionEmpty"
                    @click="handleCommand(CommandType.BLOCK_WEBSITE)"
                >
                    <GlobeIcon class="h-6 w-6 text-orange-500" />
                    <span class="text-xs text-white">Block Website</span>
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

        <!-- Block Website Dialog -->
        <Dialog v-model:open="showBlockWebsiteDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Block Website</DialogTitle>
                    <DialogDescription> Enter the website URL you want to block on the selected computers. </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="website-url" class="text-sm font-medium">Website URL</Label>
                        <Input
                            id="website-url"
                            v-model="websiteToBlock"
                            placeholder="Enter website URL (e.g., facebook.com)"
                            class="w-full"
                            @keyup.enter="submitBlockWebsite"
                        />
                        <p v-if="websiteError" class="mt-1 text-sm text-red-500">{{ websiteError }}</p>
                        <p class="text-xs text-muted-foreground">
                            Specify domain only without http:// or https:// prefix if you want to block both secure and non-secure versions. For
                            multiple sites, separate them with commas (e.g., facebook.com, twitter.com, instagram.com)
                        </p>
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button variant="outline" @click="cancelBlockWebsite">Cancel</Button>
                    <Button variant="default" @click="submitBlockWebsite" class="ml-2">Block Website</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Raw Command Dialog -->
        <Dialog v-model:open="showRawCommandDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <TerminalIcon class="h-5 w-5 text-purple-600" />
                        Raw Command (Expert Mode)
                    </DialogTitle>
                    <DialogDescription> Execute custom commands directly on selected computers. Use with caution. </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="command-name">Command Name</Label>
                        <Input
                            id="command-name"
                            v-model="commandName"
                            placeholder="e.g., ipconfig, systeminfo, tasklist"
                            class="font-mono"
                            @keydown.enter="submitRawCommand"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="command-args">Arguments (Optional)</Label>
                        <Textarea
                            id="command-args"
                            v-model="commandArgs"
                            placeholder="e.g., /all, -s, --verbose"
                            class="font-mono text-sm"
                            rows="3"
                        />
                    </div>

                    <div class="rounded-md bg-yellow-50 p-3 text-sm">
                        <div class="font-medium text-yellow-800">⚠️ Expert Mode Warning</div>
                        <div class="text-yellow-700">
                            Raw commands are executed directly on the target systems. Ensure you understand the impact before running.
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button variant="outline" @click="cancelRawCommand">Cancel</Button>
                    <Button @click="submitRawCommand" :disabled="!commandName.trim()" class="bg-purple-600 hover:bg-purple-700">
                        Execute Command
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Function Not Available Dialog -->
        <Dialog v-model:open="showNotAvailableDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Function Not Available</DialogTitle>
                    <DialogDescription> This function is not available now. Please try again later. </DialogDescription>
                </DialogHeader>

                <DialogFooter class="mt-6">
                    <Button variant="default" @click="closeNotAvailableDialog">OK</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
