<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useClipboard } from '@vueuse/core';
import axios from 'axios';
import { CheckIcon, CopyIcon, DownloadIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ScrollArea from '../ui/scroll-area/ScrollArea.vue';

// Rest of the script remains unchanged
const props = defineProps<{
    isOpen: boolean;
    roomId: string;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

// OS options
const osType = ref('windows');
const serverUrl = ref(window.location.origin);
const autoRegister = ref(true);
const includeRoomInfo = ref(true);
const scriptContent = ref('');
const isLoading = ref(false);
const activeTab = ref('bash');

// For copy functionality
const { copy, copied } = useClipboard();

// Close dialog
const closeDialog = () => {
    emit('update:isOpen', false);
};

// Generate script based on selected options
const generateScript = async () => {
    isLoading.value = true;

    try {
        const { data } = await axios.post(route('rooms.installation-script.generate'), {
            os_type: osType.value,
            server_url: serverUrl.value,
            room_id: includeRoomInfo.value ? props.roomId : null,
            auto_register: autoRegister.value,
        });

        scriptContent.value = data.script;
    } catch (error) {
        console.error('Failed to generate installation script:', error);
    } finally {
        isLoading.value = false;
    }
};

// Generate script when dialog opens
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            generateScript();
        }
    },
    { immediate: true },
);

// Handle script download
const downloadScript = () => {
    const filename = `install-agent-${osType.value}.${osType.value === 'windows' ? 'ps1' : 'sh'}`;
    const blob = new Blob([scriptContent.value], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

// Change script type and regenerate
watch(osType, () => {
    generateScript();
    // Set appropriate tab based on OS
    activeTab.value = osType.value === 'windows' ? 'powershell' : 'bash';
});

// Computed script file extension
const scriptExtension = computed(() => {
    return osType.value === 'windows' ? 'ps1' : 'sh';
});

// Generate installation command
const installCommand = computed(() => {
    if (osType.value === 'windows') {
        return `powershell -ExecutionPolicy Bypass -File .\\install-agent.ps1`;
    } else {
        return `chmod +x ./install-agent.sh && sudo ./install-agent.sh`;
    }
});
</script>

<template>
    <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
        <!-- Add max-height and overflow handling to DialogContent -->
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-3xl">
            <DialogHeader class="flex-shrink-0">
                <DialogTitle>Agent Installation Script</DialogTitle>
                <DialogDescription>
                    Generate an installation script for your agent. Run this script on computers to connect them to this management system.
                </DialogDescription>
            </DialogHeader>

            <!-- Wrap content in ScrollArea for proper scrolling -->
            <div class="grid flex-1 gap-4 overflow-y-auto py-3">
                <ScrollArea>
                    <!-- Script Options -->
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="space-y-3">
                            <div>
                                <Label>Operating System</Label>
                                <RadioGroup v-model="osType" class="mt-1.5 flex gap-4">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="windows" value="windows" />
                                        <Label for="windows">Windows</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="linux" value="linux" />
                                        <Label for="linux">Linux</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="mac" value="mac" />
                                        <Label for="mac">macOS</Label>
                                    </div>
                                </RadioGroup>
                            </div>

                            <div>
                                <Label for="serverUrl">Server URL</Label>
                                <Input id="serverUrl" v-model="serverUrl" placeholder="https://your-server.com" />
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="autoRegister" v-model="autoRegister" class="h-4 w-4 rounded border-gray-300" />
                                <Label for="autoRegister">Auto-register agent on installation</Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="includeRoom" v-model="includeRoomInfo" class="h-4 w-4 rounded border-gray-300" />
                                <Label for="includeRoom">Include room information in script</Label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Installation Instructions</Label>
                            <div class="rounded-md border p-3 text-sm">
                                <p class="font-semibold">Step 1: Download the script</p>
                                <p class="text-xs text-muted-foreground">Use the download button below or copy the script content.</p>

                                <p class="mt-2 font-semibold">Step 2: Run the script with administrator privileges</p>
                                <code class="mt-0.5 block rounded bg-muted p-1.5 text-xs">{{ installCommand }}</code>

                                <p class="mt-2 font-semibold">Step 3: Verify installation</p>
                                <p class="text-xs text-muted-foreground">
                                    The computer should appear in your room layout after successful installation.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Generated Script -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Generated Script ({{ scriptExtension }})</Label>
                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="outline" @click="generateScript" :disabled="isLoading"> Regenerate </Button>
                                <Button size="sm" variant="outline" @click="downloadScript" :disabled="isLoading || !scriptContent">
                                    <DownloadIcon class="mr-2 h-4 w-4" />
                                    Download
                                </Button>
                                <Button size="sm" variant="outline" @click="copy(scriptContent)" :disabled="isLoading || !scriptContent">
                                    <span v-if="!copied">
                                        <CopyIcon class="mr-2 h-4 w-4" />
                                        Copy
                                    </span>
                                    <span v-else>
                                        <CheckIcon class="mr-2 h-4 w-4" />
                                        Copied
                                    </span>
                                </Button>
                            </div>
                        </div>

                        <Tabs v-model="activeTab" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="bash" :disabled="osType === 'windows'">Bash (Linux/macOS)</TabsTrigger>
                                <TabsTrigger value="powershell" :disabled="osType !== 'windows'">PowerShell (Windows)</TabsTrigger>
                            </TabsList>
                            <TabsContent value="bash">
                                <!-- Reduced rows from 12 to 8 to save vertical space -->
                                <Textarea v-model="scriptContent" rows="8" class="font-mono text-sm" readonly />
                            </TabsContent>
                            <TabsContent value="powershell">
                                <Textarea v-model="scriptContent" rows="8" class="font-mono text-sm" readonly />
                            </TabsContent>
                        </Tabs>
                    </div>
                </ScrollArea>
            </div>

            <DialogFooter class="flex-shrink-0">
                <Button @click="closeDialog">Close</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
