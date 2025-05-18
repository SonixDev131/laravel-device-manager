<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowUpCircle, File, Loader2, X } from 'lucide-vue-next';
import { ref } from 'vue';

// Define types
interface AgentStats {
    total: number;
    online: number;
    offline: number;
    idle: number;
}

// No additional types needed

interface Props {
    stats: AgentStats;
    recentComputers: any[]; // Using any to avoid type conflicts
}

// Props
defineProps<Props>();

// Toast
const { toast } = useToast();

// Dialog state
const isUpdateDialogOpen = ref(false);
const isUploadDialogOpen = ref(false);

// Update form
const updateForm = useForm({
    version: '',
    force: false,
    restart_after: false,
});

// Upload form
const uploadForm = useForm({
    version: '',
    file: null as File | null,
});

// Loading states
const uploadProgress = ref(0);

// File handling
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadError = ref('');
const isFileSelected = ref(false);
const fileInputName = ref('');

// File handlers
const handleFileChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        if (file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
            uploadError.value = 'Please upload a zip file';
            return;
        }

        uploadForm.file = file;
        fileInputName.value = file.name;
        isFileSelected.value = true;
        uploadError.value = '';
    }
};

const handleFileRemove = (): void => {
    if (fileInput.value) fileInput.value.value = '';
    uploadForm.file = null;
    fileInputName.value = '';
    isFileSelected.value = false;
};

const handleDragEnter = (e: DragEvent): void => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragOver = (e: DragEvent): void => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (): void => {
    isDragging.value = false;
};

const handleDrop = (e: DragEvent): void => {
    e.preventDefault();
    isDragging.value = false;

    if (e.dataTransfer && e.dataTransfer.files.length > 0) {
        const file = e.dataTransfer.files[0];
        if (file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
            uploadError.value = 'Please upload a zip file';
            return;
        }

        uploadForm.file = file;
        fileInputName.value = file.name;
        isFileSelected.value = true;
        uploadError.value = '';
    }
};

// Form submission handler
const onSubmit = () => {
    updateForm.post(route('agents.update-all'), {
        onSuccess: () => {
            toast({
                title: 'Success!',
                description: 'Agents update command sent successfully',
                variant: 'default',
            });
            isUpdateDialogOpen.value = false;
            updateForm.reset();
        },
        onError: (errors) => {
            toast({
                title: 'Error',
                description: (Object.values(errors)[0] as string) || 'Failed to update agents',
                variant: 'destructive',
            });
        },
    });
};

// Upload form submission handler
const onUploadSubmit = () => {
    uploadForm.post(route('agents.upload-package'), {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress && typeof progress.percentage === 'number') {
                uploadProgress.value = progress.percentage;
            }
        },
        onSuccess: () => {
            toast({
                title: 'Success!',
                description: 'Package uploaded successfully',
                variant: 'default',
            });

            isUploadDialogOpen.value = false;
            uploadForm.reset();
            handleFileRemove();
        },
        onError: (errors) => {
            uploadError.value = (Object.values(errors)[0] as string) || 'Failed to upload package';
        },
    });
};

// Close upload dialog
const closeUploadDialog = (): void => {
    isUploadDialogOpen.value = false;
    uploadForm.reset();
    handleFileRemove();
    uploadError.value = '';
};

// Computed status colors
const getStatusColor = (status: string): string => {
    switch (status.toLowerCase()) {
        case 'online':
            return 'bg-green-500';
        case 'offline':
            return 'bg-red-500';
        case 'idle':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-400';
    }
};
</script>

<template>
    <AppLayout title="Agents Management">
        <Head title="Agents Management" />

        <div class="p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Agents Management</h1>

                <div class="flex space-x-2">
                    <Dialog v-model:open="isUploadDialogOpen">
                        <DialogTrigger as-child>
                            <Button variant="outline">Upload Agent Package</Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Upload Agent Package</DialogTitle>
                                <DialogDescription> Upload a zip package file for agent installation or updates </DialogDescription>
                            </DialogHeader>

                            <form @submit.prevent="onUploadSubmit" class="grid gap-6 py-4">
                                <!-- Version input -->
                                <div>
                                    <Label for="version">Version</Label>
                                    <Input id="version" v-model="uploadForm.version" placeholder="e.g., 1.2.3" required />
                                    <p class="mt-1 text-sm text-destructive" v-if="uploadForm.errors.version">
                                        {{ uploadForm.errors.version }}
                                    </p>
                                </div>

                                <!-- File Drop Zone -->
                                <Card
                                    :class="['cursor-pointer', isDragging ? 'border-blue-500 bg-blue-50' : 'border-dashed border-gray-300']"
                                    @dragenter="handleDragEnter"
                                    @dragover="handleDragOver"
                                    @dragleave="handleDragLeave"
                                    @drop="handleDrop"
                                >
                                    <CardContent v-if="!isFileSelected" class="flex flex-col items-center justify-center space-y-2 px-6 py-7">
                                        <div class="rounded-full bg-primary/10 p-3">
                                            <ArrowUpCircle class="size-8 text-blue-500" />
                                        </div>
                                        <div class="space-y-1 text-center">
                                            <h3 class="text-base font-medium">Drag and drop your ZIP file</h3>
                                            <p class="text-sm text-muted-foreground">Or click to browse</p>
                                            <p class="mt-2 text-xs text-muted-foreground">Upload the agent package ZIP file</p>
                                        </div>
                                        <input
                                            ref="fileInput"
                                            type="file"
                                            id="zip-file-input"
                                            accept=".zip,.rar,.7zip"
                                            class="hidden"
                                            @input="handleFileChange"
                                        />
                                        <Button type="button" variant="secondary" class="mt-2" @click="fileInput?.click()"> Select File </Button>
                                        <p class="mt-1 text-sm text-destructive" v-if="uploadForm.errors.file">
                                            {{ uploadForm.errors.file }}
                                        </p>
                                    </CardContent>

                                    <CardContent v-else class="px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="rounded-full bg-blue-100 p-2">
                                                    <File class="size-5 text-blue-600" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium">{{ fileInputName }}</p>
                                                    <p class="text-xs text-muted-foreground">ZIP File</p>
                                                </div>
                                            </div>
                                            <Button type="button" variant="ghost" size="icon" @click="handleFileRemove">
                                                <X class="size-4" />
                                                <span class="sr-only">Remove file</span>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Progress indicator -->
                                <div v-if="uploadForm.processing && uploadProgress > 0" class="space-y-1">
                                    <div class="mb-1 text-xs text-muted-foreground">Uploading... {{ uploadProgress }}%</div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                        <div class="h-full bg-blue-500 transition-all" :style="{ width: `${uploadProgress}%` }" />
                                    </div>
                                </div>

                                <!-- Error message -->
                                <div v-if="uploadError" class="text-sm text-destructive">
                                    {{ uploadError }}
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="closeUploadDialog">Cancel</Button>
                                    <Button
                                        type="submit"
                                        class="bg-blue-500 text-white hover:bg-blue-600"
                                        :disabled="!isFileSelected || uploadForm.processing"
                                    >
                                        <Loader2 v-if="uploadForm.processing" class="mr-2 size-4 animate-spin" />
                                        <span v-else>Upload Package</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="isUpdateDialogOpen">
                        <DialogTrigger as-child>
                            <Button variant="default">Update All Agents</Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Update All Agents</DialogTitle>
                                <DialogDescription>
                                    This will send an update command to all online agents in the system. Are you sure?
                                </DialogDescription>
                            </DialogHeader>

                            <form @submit.prevent="onSubmit" class="space-y-4 py-2">
                                <div>
                                    <Label for="version">Version (optional)</Label>
                                    <Input id="version" v-model="updateForm.version" placeholder="e.g., 1.2.3" />
                                    <p class="mt-1 text-sm text-gray-500">Leave blank to update to the latest version</p>
                                    <p class="mt-1 text-sm text-destructive" v-if="updateForm.errors.version">
                                        {{ updateForm.errors.version }}
                                    </p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="force" v-model="updateForm.force" />
                                    <Label for="force">Force update (ignore version check)</Label>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="restart" v-model="updateForm.restart_after" />
                                    <Label for="restart">Restart agents after update</Label>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" @click="isUpdateDialogOpen = false">Cancel</Button>
                                    <Button type="submit" :disabled="updateForm.processing">
                                        <span v-if="updateForm.processing">Processing...</span>
                                        <span v-else>Update All</span>
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Error alert for package upload error -->
            <Alert v-if="uploadError && !isUploadDialogOpen" variant="destructive" class="mt-4">
                <AlertCircle class="size-4" />
                <AlertTitle>Upload Error</AlertTitle>
                <AlertDescription>{{ uploadError }}</AlertDescription>
            </Alert>

            <!-- Stats Cards -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Agents</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ stats.total }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Online</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-green-600">{{ stats.online }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Offline</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-red-600">{{ stats.offline }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Idle</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-yellow-500">{{ stats.idle }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Agents Table -->
            <div class="mt-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Agents</CardTitle>
                        <CardDescription>Recently active or updated agents</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableCaption>Recently updated agents in the system</TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Room</TableHead>
                                    <TableHead>MAC Address</TableHead>
                                    <TableHead>Version</TableHead>
                                    <TableHead>Last Seen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="agent in recentComputers" :key="agent.id">
                                    <TableCell>
                                        <Badge :class="getStatusColor(agent.status)">
                                            {{ agent.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ agent.hostname }}</TableCell>
                                    <TableCell>{{ agent.room?.name ?? '—' }}</TableCell>
                                    <TableCell>{{ agent.mac_address }}</TableCell>
                                    <TableCell>{{ agent.version ?? '—' }}</TableCell>
                                    <TableCell>{{ agent.last_heartbeat_at ? new Date(agent.last_heartbeat_at).toLocaleString() : '—' }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <CardFooter>
                        <div class="text-sm text-gray-500">Showing the most recent {{ recentComputers.length }} agents.</div>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
