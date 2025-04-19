import type { Room } from '@/types';
import { computed, ref } from 'vue';

export const useRoomDialogs = () => {
    // State
    const currentRoom = ref<Room | null>(null);
    const isCreateDialogOpen = ref<boolean>(false);
    const isEditDialogOpen = ref<boolean>(false);
    const isDeleteDialogOpen = ref<boolean>(false);
    const isEditMode = ref<boolean>(false);

    // Computed properties
    const dialogOpen = computed<boolean>({
        get: () => isCreateDialogOpen.value || isEditDialogOpen.value,
        set: (value: boolean) => {
            if (!value) {
                handleDialogClose();
            }
        },
    });

    // Methods
    const openCreateDialog = (): void => {
        isCreateDialogOpen.value = true;
        isEditMode.value = false;
        currentRoom.value = null;
    };

    const closeCreateDialog = (): void => {
        isCreateDialogOpen.value = false;
    };

    const openEditDialog = (room: Room): void => {
        currentRoom.value = { ...room };
        isEditDialogOpen.value = true;
        isEditMode.value = true;
    };

    const closeEditDialog = (): void => {
        isEditDialogOpen.value = false;
        currentRoom.value = null;
    };

    const openDeleteDialog = (room: Room): void => {
        currentRoom.value = { ...room };
        isDeleteDialogOpen.value = true;
    };

    const closeDeleteDialog = (): void => {
        isDeleteDialogOpen.value = false;
        currentRoom.value = null;
    };

    const handleDialogClose = (): void => {
        if (isEditMode.value) {
            closeEditDialog();
        } else {
            closeCreateDialog();
        }
    };

    return {
        // State
        currentRoom,
        isCreateDialogOpen,
        isEditDialogOpen,
        isDeleteDialogOpen,
        isEditMode,
        dialogOpen,

        // Methods
        openCreateDialog,
        closeCreateDialog,
        openEditDialog,
        closeEditDialog,
        openDeleteDialog,
        closeDeleteDialog,
        handleDialogClose,
    };
};
