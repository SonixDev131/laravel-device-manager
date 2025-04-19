<script setup lang="ts">
// 1. Imports
import RoomCard from '@/components/rooms/RoomCard.vue';
import { Button } from '@/components/ui/button';
import type { Room } from '@/types';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

// 2. Props/emits
interface Props {
    rooms: Room[];
    searchQuery: string;
    isLoading: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'view', id: string): void;
    (e: 'edit', room: Room): void;
    (e: 'delete', room: Room): void;
    (e: 'clearSearch'): void;
}>();

// 3. Computed properties
const filteredRooms = computed<Room[]>(() => {
    if (!props.searchQuery) return props.rooms;

    const query = props.searchQuery.toLowerCase().trim();
    return props.rooms.filter((room) => room.name.toLowerCase().includes(query));
});
</script>

<template>
    <div>
        <!-- Grid display for rooms -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <RoomCard
                v-for="room in filteredRooms"
                :key="room.id"
                :room="room"
                @view="emit('view', room.id)"
                @edit="emit('edit', room)"
                @delete="emit('delete', room)"
                :is-loading="isLoading"
            />
        </div>

        <!-- No search results message -->
        <div v-if="filteredRooms.length === 0" class="mt-8 text-center text-gray-500">
            <Search class="mx-auto mb-2 size-8" />
            <p>Không tìm thấy phòng nào phù hợp với từ khóa "{{ searchQuery }}"</p>
            <Button variant="ghost" class="mt-2" @click="emit('clearSearch')"> Xóa tìm kiếm </Button>
        </div>
    </div>
</template>
