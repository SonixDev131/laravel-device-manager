<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

// Define types
type OSType = 'windows' | 'linux' | 'mac';
type ScriptType = 'powershell' | 'python' | 'bash';

// Use defineModel for two-way binding
const isOpen = defineModel<boolean>('isOpen');
const roomId = defineModel<string>('roomId');

// Form values
const osType = ref<OSType>('windows');
const serverUrl = ref<string>(window.location.origin);
const autoRegister = ref<boolean>(true);
const includeRoomInfo = ref<boolean>(true);
const scriptContent = ref<string>('');
const isLoading = ref<boolean>(false);
const scriptType = ref<ScriptType>('powershell');
const usePython = ref<boolean>(false);

// For copy functionality
const { copy, copied } = useClipboard();

// Close dialog
const closeDialog = () => {
    isOpen.value = false;
};

// Generate script based on selected options
const generateScript = async () => {
    isLoading.value = true;

    try {
        const { data } = await axios.post(route('rooms.installation-script.generate'), {
            os_type: osType.value,
            server_url: serverUrl.value,
            room_id: includeRoomInfo.value ? roomId.value : null,
            auto_register: autoRegister.value,
            use_python: osType.value === 'windows' && usePython.value,
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
    () => isOpen.value,
    (isOpen) => {
        if (isOpen) {
            generateScript();
        }
    },
    { immediate: true },
);

// Handle script download
const downloadScript = () => {
    const extension = getScriptExtension();
    const filename = `install-agent-${osType.value}.${extension}`;
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

// Update script type based on OS selection and regenerate
watch(osType, () => {
    if (osType.value === 'windows') {
        scriptType.value = usePython.value ? 'python' : 'powershell';
    } else {
        scriptType.value = 'bash';
        usePython.value = false;
    }
    generateScript();
});

// Watch for python option changes
watch(usePython, () => {
    if (osType.value === 'windows') {
        scriptType.value = usePython.value ? 'python' : 'powershell';
        generateScript();
    }
});

// Get script extension based on OS and script type
const getScriptExtension = (): string => {
    if (osType.value === 'windows') {
        return usePython.value ? 'py' : 'ps1';
    }
    return 'sh';
};

// Computed script file extension
const scriptExtension = computed(() => getScriptExtension());

// Generate installation command
const installCommand = computed(() => {
    if (osType.value === 'windows') {
        if (usePython.value) {
            return `python install-agent.py`;
        }
        return `powershell -ExecutionPolicy Bypass -File .\\install-agent.ps1`;
    } else {
        return `chmod +x ./install-agent.sh && sudo ./install-agent.sh`;
    }
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
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

                            <!-- Script Type for Windows -->
                            <div v-if="osType === 'windows'">
                                <Label>Script Type</Label>
                                <RadioGroup v-model="usePython" class="mt-1.5 flex gap-4">
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="powershell" :value="false" />
                                        <Label for="powershell">PowerShell</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <RadioGroupItem id="python" :value="true" />
                                        <Label for="python">Python</Label>
                                    </div>
                                </RadioGroup>
                            </div>

                            <div>
                                <Label for="serverUrl">Server URL</Label>
                                <Input id="serverUrl" v-model="serverUrl" placeholder="https://your-server.com" />
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="autoRegister" :checked="autoRegister" @update:checked="autoRegister = $event" />
                                <Label
                                    for="autoRegister"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Auto-register agent on installation
                                </Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="includeRoom" :checked="includeRoomInfo" @update:checked="includeRoomInfo = $event" />
                                <Label
                                    for="includeRoom"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Include room information in script
                                </Label>
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
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Generated Script ({{ scriptExtension }})</Label>
                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="outline" @click="generateScript" :disabled="isLoading"> Regenerate </Button>
                                <Button size="sm" variant="outline" @click="downloadScript" :disabled="isLoading || !scriptContent">
                                    <DownloadIcon class="mr-2 h-4 w-4" />
                                    Download
                                </Button>
                                <Button size="sm" variant="outline" @click="copy(scriptContent)" :disabled="isLoading || !scriptContent">
                                    <span v-if="!copied" class="flex items-center">
                                        <CopyIcon class="mr-2 h-4 w-4" />
                                        Copy
                                    </span>
                                    <span v-else class="flex items-center">
                                        <CheckIcon class="mr-2 h-4 w-4" />
                                        Copied
                                    </span>
                                </Button>
                            </div>
                        </div>

                        <Tabs v-model="scriptType" class="w-full">
                            <TabsList class="grid w-full" :class="osType === 'windows' ? 'grid-cols-3' : 'grid-cols-1'">
                                <TabsTrigger value="powershell" :disabled="osType !== 'windows'"> PowerShell </TabsTrigger>
                                <TabsTrigger value="python" :disabled="osType !== 'windows'" @click="usePython = true"> Python </TabsTrigger>
                                <TabsTrigger value="bash" :disabled="osType === 'windows'" :class="osType === 'windows' ? '' : 'col-span-3'">
                                    Bash
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent value="powershell">
                                <Textarea v-model="scriptContent" rows="8" class="font-mono text-sm" readonly />
                            </TabsContent>
                            <TabsContent value="python">
                                <Textarea v-model="scriptContent" rows="8" class="font-mono text-sm" readonly />
                            </TabsContent>
                            <TabsContent value="bash">
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
