import { Room, RoomFormData } from '@/types';
import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useRoomStore = defineStore('room', () => {
    // State
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const currentRoom = ref<Room | null>(null);
    const isCreateDialogOpen = ref(false);
    const isEditDialogOpen = ref(false);
    const isDeleteDialogOpen = ref(false);

    // Computed
    const isEditMode = computed(() => isEditDialogOpen.value);

    // Actions
    function resetState() {
        currentRoom.value = null;
        error.value = null;
    }

    // Dialog control functions
    function openCreateDialog() {
        resetState();
        isCreateDialogOpen.value = true;
    }

    function closeCreateDialog() {
        isCreateDialogOpen.value = false;
        resetState();
    }

    function openEditDialog(room: Room) {
        resetState();
        currentRoom.value = room;
        isEditDialogOpen.value = true;
    }

    function closeEditDialog() {
        isEditDialogOpen.value = false;
        resetState();
    }

    function openDeleteDialog(room: Room) {
        resetState();
        currentRoom.value = room;
        isDeleteDialogOpen.value = true;
    }

    function closeDeleteDialog() {
        isDeleteDialogOpen.value = false;
        resetState();
    }

    // CRUD operations
    function createRoom(data: RoomFormData) {
        isLoading.value = true;
        error.value = null;

        router.post(route('rooms.store'), data, {
            onSuccess: () => {
                closeCreateDialog();
            },
            onError: (errors) => {
                error.value = Object.values(errors).flat().join(', ');
            },
            onFinish: () => {
                isLoading.value = false;
            },
            preserveScroll: true,
        });
    }

    function updateRoom(id: string, data: RoomFormData) {
        isLoading.value = true;
        error.value = null;

        router.put(route('rooms.update', id), data, {
            onSuccess: () => {
                closeEditDialog();
            },
            onError: (errors) => {
                error.value = Object.values(errors).flat().join(', ');
            },
            onFinish: () => {
                isLoading.value = false;
            },
            preserveScroll: true,
        });
    }

    function deleteRoom(id: string) {
        isLoading.value = true;
        error.value = null;

        router.delete(route('rooms.destroy', id), {
            onSuccess: () => {
                closeDeleteDialog();
            },
            onError: (errors) => {
                error.value = Object.values(errors).flat().join(', ');
            },
            onFinish: () => {
                isLoading.value = false;
            },
            preserveScroll: true,
        });
    }

    return {
        isLoading,
        error,
        currentRoom,
        isCreateDialogOpen,
        isEditDialogOpen,
        isDeleteDialogOpen,
        isEditMode,
        openCreateDialog,
        closeCreateDialog,
        openEditDialog,
        closeEditDialog,
        openDeleteDialog,
        closeDeleteDialog,
        createRoom,
        updateRoom,
        deleteRoom,
    };
});
