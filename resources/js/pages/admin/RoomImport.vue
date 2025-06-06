<template>
    <AppLayout title="Import Rooms">
        <div class="p-6">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">Import Rooms</h1>
                    <p class="mt-2 text-muted-foreground">
                        Import rooms and computers from a JSON file. This will create or update rooms and their computer layouts.
                    </p>
                </div>

                <!-- Import Form Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Upload JSON File</CardTitle>
                        <CardDescription> Select a JSON file containing room and computer data to import into the system. </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleImport" class="space-y-6">
                            <!-- File Upload -->
                            <div class="space-y-2">
                                <Label for="jsonFile">JSON File</Label>
                                <Input
                                    id="jsonFile"
                                    ref="fileInput"
                                    type="file"
                                    accept=".json"
                                    @change="handleFileChange"
                                    :class="{ 'border-red-500': importForm.errors.jsonFile }"
                                />
                                <div v-if="importForm.errors.jsonFile" class="text-sm text-red-600">
                                    {{ importForm.errors.jsonFile }}
                                </div>
                                <p class="text-sm text-muted-foreground">Only JSON files are allowed. Maximum file size: 10MB.</p>
                            </div>

                            <!-- File Preview -->
                            <div v-if="selectedFile" class="space-y-2">
                                <Label>Selected File</Label>
                                <div class="rounded-md border p-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium">{{ selectedFile.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ formatFileSize(selectedFile.size) }}</p>
                                        </div>
                                        <Button type="button" variant="outline" size="sm" @click="clearFile"> Remove </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Preview -->
                            <div v-if="previewData" class="space-y-2">
                                <Label>Data Preview</Label>
                                <div class="rounded-md border bg-muted/50 p-4">
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium">{{ previewData.rooms?.length || 0 }} room(s) found</p>
                                        <div v-if="previewData.rooms" class="space-y-1">
                                            <div
                                                v-for="(room, index) in previewData.rooms.slice(0, 3)"
                                                :key="index"
                                                class="text-sm text-muted-foreground"
                                            >
                                                • {{ room.name }} ({{ room.computers?.length || 0 }} computers)
                                            </div>
                                            <div v-if="previewData.rooms.length > 3" class="text-sm text-muted-foreground">
                                                ... and {{ previewData.rooms.length - 3 }} more room(s)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex space-x-3">
                                <Button type="submit" :disabled="!selectedFile || importForm.processing" class="flex-1">
                                    <span v-if="importForm.processing">Importing...</span>
                                    <span v-else>Import Data</span>
                                </Button>
                                <Button type="button" variant="outline" @click="showExampleDialog = true"> View Example </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Import History/Status -->
                <Card>
                    <CardHeader>
                        <CardTitle>Import Guidelines</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="rounded-md bg-blue-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Important Notes</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <ul class="list-inside list-disc space-y-1">
                                                <li>Rooms with the same name will be updated with new data</li>
                                                <li>Computers are matched by MAC address</li>
                                                <li>Grid positions (pos_row, pos_col) define computer layout</li>
                                                <li>Grid rows and columns define the room layout size</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Example JSON Dialog -->
                <Dialog v-model:open="showExampleDialog">
                    <DialogContent class="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Example JSON Format</DialogTitle>
                            <DialogDescription> Use this format for your JSON import file. </DialogDescription>
                        </DialogHeader>
                        <div class="mt-4">
                            <Label>JSON Structure</Label>
                            <div class="mt-2 rounded-md bg-muted p-4 font-mono text-sm">
                                <pre>{{ exampleJson }}</pre>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button @click="showExampleDialog = false">Close</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Form and state
const importForm = useForm({
    jsonFile: null as File | null,
});

const selectedFile = ref<File | null>(null);
const previewData = ref<any>(null);
const showExampleDialog = ref(false);
const fileInput = ref<HTMLInputElement>();

// Example JSON structure
const exampleJson = `{
  "rooms": [
    {
      "name": "Computer Lab 1",
      "grid_rows": 5,
      "grid_cols": 6,
      "computers": [
        {
          "hostname": "lab1-pc01",
          "mac_address": "00:11:22:33:44:55",
          "pos_row": 1,
          "pos_col": 1
        },
        {
          "hostname": "lab1-pc02", 
          "mac_address": "00:11:22:33:44:56",
          "pos_row": 1,
          "pos_col": 2
        }
      ]
    }
  ]
}`;

// File handling
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        selectedFile.value = file;
        importForm.jsonFile = file;
        previewFile(file);
    }
};

const clearFile = () => {
    selectedFile.value = null;
    previewData.value = null;
    importForm.jsonFile = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const previewFile = async (file: File) => {
    try {
        const text = await file.text();
        const data = JSON.parse(text);
        previewData.value = data;
    } catch (error) {
        console.error('Error parsing JSON:', error);
        previewData.value = null;
    }
};

const handleImport = () => {
    if (!selectedFile.value) return;

    importForm.post(route('rooms.import'), {
        onSuccess: () => {
            clearFile();
        },
        onError: () => {
            // Keep file selected on error for retry
        },
    });
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>
