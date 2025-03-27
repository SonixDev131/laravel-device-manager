<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { useRoomStore } from '@/stores/room';
import { Room, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { AlertCircle, FolderOpen, Loader2, PlusCircle, Search } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, ref, watch } from 'vue';
import RoomCard from './components/RoomCard.vue';
import RoomDialog from './components/RoomDialog.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rooms',
        href: '/rooms',
    },
];

// Props definition with TypeScript typing
const props = defineProps<{
    rooms: { data: Room[] };
}>();

// Store initialization
const roomStore = useRoomStore();

const { isLoading, error, currentRoom, isCreateDialogOpen, isEditDialogOpen, isDeleteDialogOpen, isEditMode } = storeToRefs(roomStore);

const { openEditDialog, closeEditDialog, openDeleteDialog, closeDeleteDialog, openCreateDialog, closeCreateDialog } = roomStore;

// Component state
const searchQuery = ref('');
const isSearching = ref(false);
const currentPage = ref(1);

// Reset to first page when search query changes
watch(searchQuery, () => {
    currentPage.value = 1;
    isSearching.value = true;
    debouncedSearch();
});

// Debounce search to improve performance
const debouncedSearch = useDebounceFn(() => {
    isSearching.value = false;
}, 300);

// Navigation helper
const viewRoomDetails = (id: string) => {
    router.get(route('rooms.layout', id));
};

// Dialog handlers
const handleEditRoom = (room: Room) => {
    openEditDialog(room);
};

const handleDeleteRoom = (room: Room) => {
    openDeleteDialog(room);
};

// Handle dialog submission
const handleDialogSubmit = (formData) => {
    if (isEditMode.value) {
        // Send update request
        router.put(route('rooms.update', formData.id), formData, {
            onStart: () => (roomStore.isLoading = true),
            onFinish: () => {
                closeEditDialog();
                roomStore.isLoading = false;
            },
            preserveScroll: true,
        });
    } else {
        // Send create request
        router.post(route('rooms.store'), formData, {
            onStart: () => (roomStore.isLoading = true),
            onFinish: () => {
                closeCreateDialog();
                roomStore.isLoading = false;
            },
            preserveScroll: true,
        });
    }
};

// Handle dialog closure
const handleDialogClose = () => {
    if (isEditMode.value) {
        closeEditDialog();
    } else {
        closeCreateDialog();
    }
};

const dialogOpen = computed({
    get: () => isCreateDialogOpen.value || isEditDialogOpen.value,
    set: (value: boolean) => {
        if (!value) {
            handleDialogClose();
        }
    },
});

// Filtered rooms based on search
const filteredRooms = computed(() => {
    if (!searchQuery.value) return props.rooms.data;

    const query = searchQuery.value.toLowerCase().trim();
    return props.rooms.data.filter((room) => room.name.toLowerCase().includes(query));
});
</script>

<template>
    <Head title="Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 rounded-xl md:min-h-min">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <!--* Search input with debounce -->
                    <div class="relative w-full max-w-sm">
                        <Input id="search" v-model="searchQuery" type="text" placeholder="Tìm phòng theo tên..." class="pl-10" />
                        <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
                            <Search v-if="!isSearching" class="size-5 text-muted-foreground" />
                            <Loader2 v-else class="size-5 animate-spin text-muted-foreground" />
                        </span>
                    </div>

                    <!--* Button to add a new room -->
                    <Button class="bg-blue-500 text-white hover:bg-blue-600" @click="openCreateDialog()" :disabled="isLoading">
                        <PlusCircle v-if="!isLoading" class="mr-2 size-4" />
                        <Loader2 v-else class="mr-2 size-4 animate-spin" />
                        Thêm Phòng
                    </Button>
                </div>

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
                <div v-else-if="props.rooms.data.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                    <FolderOpen class="size-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-medium">Chưa có phòng nào</h3>
                    <p class="text-muted-foreground">Bắt đầu tạo một phòng mới bằng cách nhấn nút "Thêm Phòng"</p>
                </div>
                <!-- Room grid -->
                <div v-else>
                    <!-- Grid display for rooms -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <RoomCard
                            v-for="room in filteredRooms"
                            :key="room.id"
                            :room="room"
                            @view="viewRoomDetails(room.id)"
                            @edit="handleEditRoom(room)"
                            @delete="handleDeleteRoom(room)"
                            :is-loading="isLoading"
                        />
                    </div>

                    <!-- No search results message -->
                    <div v-if="filteredRooms.length === 0" class="mt-8 text-center text-gray-500">
                        <Search class="mx-auto mb-2 size-8" />
                        <p>Không tìm thấy phòng nào phù hợp với từ khóa "{{ searchQuery }}"</p>
                        <Button variant="ghost" class="mt-2" @click="searchQuery = ''"> Xóa tìm kiếm </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Centralized create/edit dialog -->
        <RoomDialog
            :form-id="isEditMode ? `editRoomForm-${currentRoom?.id}` : 'createRoomForm'"
            :is-edit="isEditMode"
            :room="currentRoom"
            v-model:is-open="dialogOpen"
            @submit="handleDialogSubmit"
        />
    </AppLayout>
</template>
