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
import { AlertCircle, ArrowUpCircle, Download, File, Loader2, Trash2, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

// Define types
interface AgentStats {
    total: number;
    online: number;
    offline: number;
    idle: number;
}

interface AgentPackage {
    id: string;
    name: string;
    file_name: string;
    version: string;
    size: number;
    is_latest: boolean;
    created_at: string;
}

interface Installer {
    id: string;
    name: string;
    description: string;
    file_name: string;
    file_size: number;
    auto_install: boolean;
    install_args: string | null;
    created_at: string;
}

interface Props {
    stats: AgentStats;
    recentComputers: any[]; // Using any to avoid type conflicts
    packages: AgentPackage[];
    installers: Installer[];
}

// Props
defineProps<Props>();

// Toast
const { toast } = useToast();

// Dialog state
const isUpdateDialogOpen = ref(false);
const isUploadDialogOpen = ref(false);
const isInstallerDialogOpen = ref(false);

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

// Installer upload form
const installerForm = useForm({
    name: '',
    description: '',
    file: null as File | null,
    auto_install: false,
    install_args: '',
});

// Loading states
const uploadProgress = ref(0);

// File handling
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadError = ref('');
const isFileSelected = ref(false);
const fileInputName = ref('');

// Installer handling
const installerFileInput = ref<HTMLInputElement | null>(null);
const isInstallerFileSelected = ref(false);
const installerFileName = ref('');
const installerError = ref('');

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

// Delete package function
const deletePackage = (packageId: string): void => {
    if (confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
        const deleteForm = useForm({});
        deleteForm.delete(route('agents.packages.delete', packageId), {
            onSuccess: () => {
                toast({
                    title: 'Success!',
                    description: 'Package deleted successfully',
                    variant: 'default',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Error',
                    description: (Object.values(errors)[0] as string) || 'Failed to delete package',
                    variant: 'destructive',
                });
            },
        });
    }
};

// Format file size function
const formatFileSize = (bytes: number): string => {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(1)} ${units[unitIndex]}`;
};

// Handle installer file selection
const handleInstallerFileChange = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        installerForm.file = file;
        installerFileName.value = file.name;
        isInstallerFileSelected.value = true;
        installerError.value = '';
    }
};

// Remove installer file
const handleInstallerFileRemove = (): void => {
    if (installerFileInput.value) installerFileInput.value.value = '';
    installerForm.file = null;
    installerFileName.value = '';
    isInstallerFileSelected.value = false;
};

// Submit installer upload
const onInstallerSubmit = (): void => {
    installerForm.post(route('agents.upload-installer'), {
        forceFormData: true,
        onSuccess: () => {
            toast({
                title: 'Success!',
                description: 'Installer uploaded successfully',
                variant: 'default',
            });
            closeInstallerDialog();
        },
        onError: (errors) => {
            installerError.value = (Object.values(errors)[0] as string) || 'Failed to upload installer';
        },
    });
};

// Close installer dialog
const closeInstallerDialog = (): void => {
    isInstallerDialogOpen.value = false;
    installerForm.reset();
    handleInstallerFileRemove();
    installerError.value = '';
};

// Broadcast installer
const broadcastInstaller = (installerId: string): void => {
    if (confirm('Send download command to all agents? This will start downloading the installer on all connected agents.')) {
        const broadcastForm = useForm({});
        broadcastForm.post(route('agents.installers.broadcast', installerId), {
            onSuccess: () => {
                toast({
                    title: 'Success!',
                    description: 'Installer download command sent to all agents',
                    variant: 'default',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Error',
                    description: (Object.values(errors)[0] as string) || 'Failed to broadcast installer',
                    variant: 'destructive',
                });
            },
        });
    }
};

// Delete installer
const deleteInstaller = (installerId: string): void => {
    if (confirm('Are you sure you want to delete this installer? This action cannot be undone.')) {
        const deleteForm = useForm({});
        deleteForm.delete(route('agents.installers.delete', installerId), {
            onSuccess: () => {
                toast({
                    title: 'Success!',
                    description: 'Installer deleted successfully',
                    variant: 'default',
                });
            },
            onError: (errors) => {
                toast({
                    title: 'Error',
                    description: (Object.values(errors)[0] as string) || 'Failed to delete installer',
                    variant: 'destructive',
                });
            },
        });
    }
};
</script>

<template>
    <AppLayout title="Agents Management">
        <div class="p-6">
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

                        <Dialog v-model:open="isInstallerDialogOpen">
                            <DialogTrigger as-child>
                                <Button variant="outline">Upload Installer</Button>
                            </DialogTrigger>

                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Upload Installer</DialogTitle>
                                    <DialogDescription>Upload an installer file to be deployed to agents</DialogDescription>
                                </DialogHeader>

                                <form @submit.prevent="onInstallerSubmit" class="grid gap-6 py-4">
                                    <!-- Name input -->
                                    <div>
                                        <Label for="installer-name">Name</Label>
                                        <Input id="installer-name" v-model="installerForm.name" placeholder="e.g., Google Chrome" required />
                                        <p class="mt-1 text-sm text-destructive" v-if="installerForm.errors.name">
                                            {{ installerForm.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Description input -->
                                    <div>
                                        <Label for="installer-description">Description (optional)</Label>
                                        <Input
                                            id="installer-description"
                                            v-model="installerForm.description"
                                            placeholder="Brief description of the installer"
                                        />
                                        <p class="mt-1 text-sm text-destructive" v-if="installerForm.errors.description">
                                            {{ installerForm.errors.description }}
                                        </p>
                                    </div>

                                    <!-- Auto-install checkbox -->
                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="auto-install" v-model="installerForm.auto_install" />
                                        <Label for="auto-install">Auto-install after download</Label>
                                    </div>

                                    <!-- Install arguments -->
                                    <div v-if="installerForm.auto_install">
                                        <Label for="install-args">Installation Arguments (optional)</Label>
                                        <Input id="install-args" v-model="installerForm.install_args" placeholder="/silent /norestart" />
                                        <p class="mt-1 text-sm text-gray-500">Command line arguments for silent installation</p>
                                    </div>

                                    <!-- File Drop Zone -->
                                    <Card class="cursor-pointer border-dashed border-gray-300" @click="installerFileInput?.click()">
                                        <CardContent
                                            v-if="!isInstallerFileSelected"
                                            class="flex flex-col items-center justify-center space-y-2 px-6 py-7"
                                        >
                                            <div class="rounded-full bg-primary/10 p-3">
                                                <Upload class="size-8 text-blue-500" />
                                            </div>
                                            <div class="space-y-1 text-center">
                                                <h3 class="text-base font-medium">Select installer file</h3>
                                                <p class="text-sm text-muted-foreground">Click to browse</p>
                                            </div>
                                            <input ref="installerFileInput" type="file" class="hidden" @change="handleInstallerFileChange" />
                                            <p class="mt-1 text-sm text-destructive" v-if="installerForm.errors.file">
                                                {{ installerForm.errors.file }}
                                            </p>
                                        </CardContent>

                                        <CardContent v-else class="px-6 py-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="rounded-full bg-blue-100 p-2">
                                                        <File class="size-5 text-blue-600" />
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium">{{ installerFileName }}</p>
                                                        <p class="text-xs text-muted-foreground">Installer File</p>
                                                    </div>
                                                </div>
                                                <Button type="button" variant="ghost" size="icon" @click.stop="handleInstallerFileRemove">
                                                    <X class="size-4" />
                                                    <span class="sr-only">Remove file</span>
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <!-- Error message -->
                                    <div v-if="installerError" class="text-sm text-destructive">
                                        {{ installerError }}
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="closeInstallerDialog">Cancel</Button>
                                        <Button
                                            type="submit"
                                            class="bg-blue-500 text-white hover:bg-blue-600"
                                            :disabled="!isInstallerFileSelected || installerForm.processing"
                                        >
                                            <Loader2 v-if="installerForm.processing" class="mr-2 size-4 animate-spin" />
                                            <span v-else>Upload Installer</span>
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
                                        <TableCell>{{
                                            agent.last_heartbeat_at ? new Date(agent.last_heartbeat_at).toLocaleString() : '—'
                                        }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                        <CardFooter>
                            <div class="text-sm text-gray-500">Showing the most recent {{ recentComputers.length }} agents.</div>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Agent Packages Management -->
                <div class="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Agent Packages</CardTitle>
                            <CardDescription>Manage uploaded agent packages for deployment</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table v-if="packages.length > 0">
                                <TableCaption>All uploaded agent packages</TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Version</TableHead>
                                        <TableHead>File Name</TableHead>
                                        <TableHead>Size</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Uploaded</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="pkg in packages" :key="pkg.id">
                                        <TableCell class="font-medium">{{ pkg.version }}</TableCell>
                                        <TableCell>{{ pkg.file_name }}</TableCell>
                                        <TableCell>{{ formatFileSize(pkg.size) }}</TableCell>
                                        <TableCell>
                                            <Badge v-if="pkg.is_latest" class="bg-green-500 text-white">Latest</Badge>
                                            <Badge v-else variant="outline">Archive</Badge>
                                        </TableCell>
                                        <TableCell>{{ new Date(pkg.created_at).toLocaleString() }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deletePackage(pkg.id)"
                                                class="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                <span class="sr-only">Delete package</span>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="py-8 text-center">
                                <File class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No packages uploaded</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by uploading your first agent package.</p>
                            </div>
                        </CardContent>
                        <CardFooter v-if="packages.length > 0">
                            <div class="text-sm text-gray-500">Total {{ packages.length }} package(s) uploaded.</div>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Installer Management -->
                <div class="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Installer Management</CardTitle>
                            <CardDescription>Manage and deploy installer files to all agents</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table v-if="installers.length > 0">
                                <TableCaption>All uploaded installer files</TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>File Name</TableHead>
                                        <TableHead>Size</TableHead>
                                        <TableHead>Auto Install</TableHead>
                                        <TableHead>Uploaded</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="installer in installers" :key="installer.id">
                                        <TableCell class="font-medium">{{ installer.name }}</TableCell>
                                        <TableCell>{{ installer.description || '—' }}</TableCell>
                                        <TableCell>{{ installer.file_name }}</TableCell>
                                        <TableCell>{{ formatFileSize(installer.file_size) }}</TableCell>
                                        <TableCell>
                                            <Badge v-if="installer.auto_install" class="bg-green-500 text-white">Yes</Badge>
                                            <Badge v-else variant="outline">No</Badge>
                                        </TableCell>
                                        <TableCell>{{ new Date(installer.created_at).toLocaleString() }}</TableCell>
                                        <TableCell class="text-right">
                                            <div class="flex justify-end space-x-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    @click="broadcastInstaller(installer.id)"
                                                    class="h-8 w-8 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                                    title="Send to all agents"
                                                >
                                                    <Download class="h-4 w-4" />
                                                    <span class="sr-only">Broadcast installer</span>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    @click="deleteInstaller(installer.id)"
                                                    class="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    title="Delete installer"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    <span class="sr-only">Delete installer</span>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="py-8 text-center">
                                <Upload class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No installers uploaded</h3>
                                <p class="mt-1 text-sm text-gray-500">Upload installer files to deploy them to your agents.</p>
                            </div>
                        </CardContent>
                        <CardFooter v-if="installers.length > 0">
                            <div class="text-sm text-gray-500">Total {{ installers.length }} installer(s) available.</div>
                        </CardFooter>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
