<script setup lang="ts">
// 1. Imports
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useRoomImport } from '@/composables/useRoomImport';
import { AlertCircle, ArrowUpCircle, File, Loader2, X } from 'lucide-vue-next';

// 2. Props/emits
const emit = defineEmits<{
    imported: [];
}>();

// 3. Reactive state
const {
    fileInput,
    isFileSelected,
    importError,
    fileInputName,
    isDragging,
    importForm,
    handleFileChange,
    handleDragEnter,
    handleDragOver,
    handleDragLeave,
    handleDrop,
    handleFileRemove,
    closeImportDialog,
    submitImport,
} = useRoomImport();

const isOpen = defineModel<boolean>('isOpen', { default: false });

// 4. Methods
const closeDialog = (): void => {
    closeImportDialog();
    isOpen.value = false;
};

const onSubmit = (): void => {
    submitImport();
    // Listen for the room-imported event
    const handleImported = (): void => {
        emit('imported');
        window.removeEventListener('room-imported', handleImported);
    };

    window.addEventListener('room-imported', handleImported);
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Import Rooms from JSON</DialogTitle>
                <p class="text-sm text-muted-foreground">Upload a JSON file with rooms and computers data</p>
            </DialogHeader>

            <form @submit.prevent="onSubmit" class="grid gap-6 py-4">
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
                            <h3 class="text-base font-medium">Drag and drop your JSON file</h3>
                            <p class="text-sm text-muted-foreground">Or click to browse</p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                JSON file must follow the required format:
                                <code class="rounded bg-blue-50 px-1 text-blue-600">{ "rooms": [...] }</code>
                            </p>
                        </div>
                        <input ref="fileInput" type="file" id="json-file-input" accept="application/json" class="hidden" @input="handleFileChange" />
                        <Button type="button" variant="secondary" class="mt-2" @click="fileInput?.click()"> Select File </Button>
                    </CardContent>

                    <CardContent v-else class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-full bg-blue-100 p-2">
                                    <File class="size-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ fileInputName }}</p>
                                    <p class="text-xs text-muted-foreground">JSON File</p>
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
                <div v-if="importForm.progress" class="space-y-1">
                    <div class="mb-1 text-xs text-muted-foreground">Uploading... {{ importForm.progress.percentage }}%</div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full bg-blue-500 transition-all" :style="{ width: `${importForm.progress.percentage}%` }" />
                    </div>
                </div>

                <!-- Error message -->
                <div v-if="importError" class="text-sm text-destructive">
                    {{ importError }}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeDialog"> Cancel </Button>
                    <Button type="submit" class="bg-blue-500 text-white hover:bg-blue-600" :disabled="!isFileSelected || importForm.processing">
                        <Loader2 v-if="importForm.processing" class="mr-2 size-4 animate-spin" />
                        <span v-else>Import Rooms</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Import error alert for display outside the dialog -->
    <Alert v-if="importError && !isOpen" variant="destructive" class="mb-6">
        <AlertCircle class="size-4" />
        <AlertTitle>Import Error</AlertTitle>
        <AlertDescription>{{ importError }}</AlertDescription>
    </Alert>
</template>
