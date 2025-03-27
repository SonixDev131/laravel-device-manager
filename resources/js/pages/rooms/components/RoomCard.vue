<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useRoomStore } from '@/stores/room';
import { Room } from '@/types';
import { Eye, LayoutGrid, PencilLine, Trash } from 'lucide-vue-next';

// Props definition
const props = defineProps<{
    room: Room;
    isLoading?: boolean;
}>();

// Events emitted by this component
const emit = defineEmits<{
    view: [id: string];
    edit: [room: Room];
    delete: [room: Room];
}>();

const roomStore = useRoomStore();

const handleView = () => {
    emit('view', props.room.id);
};

const handleEdit = () => {
    emit('edit', props.room);
};

const handleDelete = () => {
    emit('delete', props.room);
};

const handleDeleteRoom = () => {
    roomStore.deleteRoom(props.room.id);
};

// Compute status color based on room availability
// const statusColor = computed(() => {
//     return props.room.is_active
//         ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400'
//         : 'bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-400';
// });
</script>
<template>
    <Card class="group relative flex h-full flex-col overflow-hidden transition-all duration-300 hover:shadow-lg dark:hover:shadow-primary/5">
        <!-- Room Header with Status Badge -->
        <CardHeader class="space-y-1 pb-2">
            <div class="flex items-start justify-between">
                <CardTitle class="line-clamp-1 flex-1 text-lg" :title="room.name">
                    {{ room.name }}
                </CardTitle>
                <!-- <Badge :variant="room.status === 'Hoạt động' ? 'default' : 'outline'" class="ml-2">
                    {{ room.status || 'Hoạt động' }}
                </Badge> -->
            </div>
            <CardDescription class="flex items-center gap-2 text-sm">
                <LayoutGrid class="size-4 text-muted-foreground" />
                {{ room.grid_rows }} x {{ room.grid_cols }}
            </CardDescription>
        </CardHeader>

        <!-- Room Content -->
        <CardContent class="flex-1">
            <div class="space-y-4">
                <!-- Room Layout Visual Representation -->
                <div class="flex aspect-video items-center justify-center rounded-md bg-muted/40 p-2">
                    <div
                        class="grid place-items-center gap-1"
                        :style="{
                            gridTemplateColumns: `repeat(${Math.min(room.grid_cols, 8)}, 1fr)`,
                            gridTemplateRows: `repeat(${Math.min(room.grid_rows, 5)}, 1fr)`,
                        }"
                    >
                        <div
                            v-for="i in Math.min(room.grid_rows * room.grid_cols, 40)"
                            :key="i"
                            class="size-3 rounded-sm bg-primary/20 transition-colors group-hover:bg-primary/30"
                        ></div>
                    </div>
                </div>

                <!-- Room Details
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <Layout class="size-3.5 text-muted-foreground" />
                            <span class="text-xs text-muted-foreground">Loại phòng:</span>
                        </div>
                        <p class="font-medium">{{ room.type || 'Không có' }}</p>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                            <MonitorSmartphone class="size-3.5 text-muted-foreground" />
                            <span class="text-xs text-muted-foreground">Thiết bị:</span>
                        </div>
                        <p class="font-medium">{{ room.equipment_count || 0 }}</p>
                    </div>
                </div> -->
            </div>
        </CardContent>

        <!-- Action Buttons -->
        <CardFooter class="border-t bg-muted/20 p-3">
            <div class="grid w-full grid-cols-3 gap-2">
                <Button variant="outline" class="w-full group-hover:border-primary/50 group-hover:text-primary" size="sm" @click.stop="handleView">
                    <Eye class="mr-1 size-4" />
                    <span class="sr-only sm:not-sr-only sm:inline">Chi tiết</span>
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    class="col-span-1 group-hover:border-amber-500/50 group-hover:text-amber-600 dark:group-hover:text-amber-500"
                    @click.stop="handleEdit"
                >
                    <PencilLine class="mr-1 size-4" />
                    <span class="sr-only sm:not-sr-only sm:inline">Sửa</span>
                </Button>

                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <div class="col-span-1">
                                <AlertDialog :open="roomStore.isDeleteDialogOpen && roomStore.currentRoom?.id === room.id">
                                    <AlertDialogTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="w-full group-hover:border-rose-500/50 group-hover:text-rose-600 dark:group-hover:text-rose-500"
                                            @click.stop="handleDelete"
                                        >
                                            <Trash class="mr-1 size-4" />
                                            <span class="sr-only sm:not-sr-only sm:inline">Xóa</span>
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Bạn có chắc chắn không?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Hành động này không thể hoàn tác. Phòng này sẽ bị xóa vĩnh viễn và dữ liệu của nó sẽ bị xóa khỏi máy
                                                chủ.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel @click="roomStore.closeDeleteDialog()">Hủy bỏ</AlertDialogCancel>
                                            <AlertDialogAction @click.stop="handleDeleteRoom">Xác nhận</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Xóa phòng</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </CardFooter>
    </Card>
</template>
