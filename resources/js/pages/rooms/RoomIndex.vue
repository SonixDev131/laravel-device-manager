<script setup lang="ts">
// 1. Imports
import RoomDialog from '@/components/rooms/RoomDialog.vue';
import RoomEmptyState from '@/components/rooms/RoomEmptyState.vue';
import RoomGrid from '@/components/rooms/RoomGrid.vue';
import RoomImportDialog from '@/components/rooms/RoomImportDialog.vue';
import RoomSearch from '@/components/rooms/RoomSearch.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/toast/use-toast';
import { useRoomDialogs } from '@/composables/useRoomDialogs';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Room } from '@/types';
import { FormDataConvertible } from '@inertiajs/core';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowUpCircle, Loader2, PlusCircle } from 'lucide-vue-next';
import { ref } from 'vue';

// 2. Props/Emits definition
interface Props {
    rooms: Room[];
}

const props = defineProps<Props>();

// 3. Reactive state
// UI state
const { toast } = useToast();
const isLoading = ref<boolean>(false);
const error = ref<string | null>(null);
const searchQuery = ref<string>('');
const isImportDialogOpen = ref<boolean>(false);

// Navigation
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rooms',
        href: '/rooms',
    },
];

// Dialog state from composable
const { currentRoom, isEditMode, dialogOpen, openCreateDialog, openEditDialog, openDeleteDialog } = useRoomDialogs();

// 4. Methods
// Room CRUD operations
const createRoom = (roomData: Partial<Room>): void => {
    isLoading.value = true;
    error.value = null;

    // Create a FormData compatible object from roomData
    const formData = { ...roomData } as Record<string, FormDataConvertible>;

    router.post(route('rooms.store'), formData, {
        onSuccess: () => {
            // closeCreateDialog();
            toast({
                title: 'Room Created',
                description: 'New room has been created successfully',
                variant: 'default',
            });
        },
        onError: (errors) => {
            error.value = errors.message || 'Failed to create room';
            toast({
                title: 'Error',
                description: error.value,
                variant: 'destructive',
            });
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const updateRoom = (roomData: Partial<Room>): void => {
    if (!currentRoom.value) return;

    isLoading.value = true;
    error.value = null;

    // Create a FormData compatible object from roomData
    const formData = { ...roomData } as Record<string, FormDataConvertible>;

    router.put(route('rooms.update', currentRoom.value.id), formData, {
        onSuccess: () => {
            // closeEditDialog();
            toast({
                title: 'Room Updated',
                description: 'Room has been updated successfully',
                variant: 'default',
            });
        },
        onError: (errors) => {
            error.value = errors.message || 'Failed to update room';
            toast({
                title: 'Error',
                description: error.value,
                variant: 'destructive',
            });
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const deleteRoom = (): void => {
    if (!currentRoom.value) return;

    isLoading.value = true;
    error.value = null;

    router.delete(route('rooms.destroy', currentRoom.value.id), {
        onSuccess: () => {
            // closeDeleteDialog();
            toast({
                title: 'Room Deleted',
                description: 'Room has been deleted successfully',
                variant: 'default',
            });
        },
        onError: (errors) => {
            error.value = errors.message || 'Failed to delete room';
            toast({
                title: 'Error',
                description: error.value,
                variant: 'destructive',
            });
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// Navigation and event handlers
const viewRoomDetails = (id: string): void => {
    router.get(route('rooms.show', id));
};

const handleImported = (): void => {
    router.reload();
};

const clearSearch = (): void => {
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 rounded-xl md:min-h-min">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Search component -->
                    <RoomSearch v-model:query="searchQuery" />

                    <!-- Action buttons group -->
                    <div class="flex gap-2">
                        <!-- Import Button -->
                        <Button variant="default" class="bg-blue-500 text-white hover:bg-blue-600" size="default" @click="isImportDialogOpen = true">
                            <ArrowUpCircle class="mr-2 size-4" />
                            <span>Import</span>
                        </Button>

                        <!-- Button to add a new room -->
                        <Button class="bg-blue-500 text-white hover:bg-blue-600" @click="openCreateDialog()" :disabled="isLoading">
                            <PlusCircle v-if="!isLoading" class="mr-2 size-4" />
                            <Loader2 v-else class="mr-2 size-4 animate-spin" />
                            Thêm Phòng
                        </Button>
                    </div>
                </div>

                <!-- Import Dialog component -->
                <RoomImportDialog v-model:is-open="isImportDialogOpen" @imported="handleImported" />

                <!-- Loading state -->
                <div v-if="isLoading" class="flex justify-center py-12">
                    <Loader2 class="size-12 animate-spin text-muted-foreground" />
                </div>

                <!-- Error state -->
                <Alert v-else-if="error" variant="destructive" class="mb-6">
                    <AlertCircle class="size-4" />
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>

                <!-- Empty state when no rooms exist -->
                <RoomEmptyState v-else-if="props.rooms.length === 0" @create="openCreateDialog" />

                <!-- Room grid -->
                <RoomGrid
                    v-else
                    :rooms="props.rooms"
                    :search-query="searchQuery"
                    :is-loading="isLoading"
                    @view="viewRoomDetails"
                    @edit="openEditDialog"
                    @delete="
                        (room) => {
                            openDeleteDialog(room);
                            deleteRoom();
                        }
                    "
                    @clear-search="clearSearch"
                />
            </div>
        </div>

        <!-- Centralized create/edit dialog -->
        <RoomDialog
            :form-id="isEditMode ? `editRoomForm-${currentRoom?.id}` : 'createRoomForm'"
            :is-edit="isEditMode"
            :room="currentRoom || undefined"
            v-model:is-open="dialogOpen"
            @submit="isEditMode ? updateRoom($event) : createRoom($event)"
        />
    </AppLayout>
</template>
