<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useRoomStore } from '@/stores/room';
import { Room, RoomFormData } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { watch } from 'vue';

interface Props {
    formId: string;
    isEdit: boolean;
    room?: Partial<Room>;
    showTrigger?: boolean;
}

const isOpen = defineModel('isOpen');
const roomStore = useRoomStore();

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    isEdit: false,
    showTrigger: false,
});

defineEmits<{
    submit: [value: RoomFormData];
}>();

// Initialize form with proper typing
const form = useForm({
    name: props.isEdit && props.room ? props.room.name || '' : '',
    grid_rows: props.isEdit && props.room ? props.room.grid_rows || 5 : 5,
    grid_cols: props.isEdit && props.room ? props.room.grid_cols || 5 : 5,
});

// Watch for room changes to update the form
watch(
    () => props.room,
    (newRoom) => {
        if (newRoom && isOpen.value) {
            form.name = newRoom.name || '';
            form.grid_rows = newRoom.grid_rows || 5;
            form.grid_cols = newRoom.grid_cols || 5;
        }
    },
    { deep: true, immediate: true },
);

const onSubmit = () => {
    if (props.isEdit && props.room?.id) {
        // Send update request
        form.put(route('rooms.update', props.room.id), {
            onStart: () => (roomStore.isLoading = true),
            onFinish: () => {
                roomStore.isLoading = false;
                // Close dialog on success
                if (!form.hasErrors) {
                    isOpen.value = false;
                }
            },
            preserveScroll: true,
        });
    } else {
        // Send create request
        form.post(route('rooms.store'), {
            onStart: () => (roomStore.isLoading = true),
            onFinish: () => {
                roomStore.isLoading = false;
                // Close dialog on success
                if (!form.hasErrors) {
                    isOpen.value = false;
                }
            },
            preserveScroll: true,
        });
    }
};
</script>
<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ isEdit ? 'Chỉnh Sửa Phòng' : 'Thêm Phòng Mới' }}</DialogTitle>
                <DialogDescription>
                    {{ isEdit ? 'Chỉnh sửa thông tin phòng' : 'Nhập thông tin phòng mới' }}
                    và nhấn Lưu khi hoàn tất.
                </DialogDescription>
            </DialogHeader>

            <form :id="formId" @submit.prevent="onSubmit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- Room Name Field -->
                    <div class="grid gap-2">
                        <Label for="name">Tên Phòng</Label>
                        <Input id="name" type="text" placeholder="Nhập tên phòng..." v-model="form.name" :disabled="form.processing" required />
                        <p class="text-xs text-muted-foreground">Đây là tên hiển thị của phòng trong hệ thống.</p>
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Grid Rows Field -->
                            <div class="grid gap-2">
                                <Label for="grid_rows">Số hàng</Label>
                                <Input
                                    id="grid_rows"
                                    type="number"
                                    placeholder="Nhập số hàng..."
                                    v-model="form.grid_rows"
                                    :disabled="form.processing"
                                    required
                                />
                                <InputError :message="form.errors.grid_rows" />
                            </div>

                            <!-- Grid Columns Field -->
                            <div class="grid gap-2">
                                <Label for="grid_cols">Số cột</Label>
                                <Input
                                    id="grid_cols"
                                    type="number"
                                    placeholder="Nhập số cột..."
                                    v-model="form.grid_cols"
                                    :disabled="form.processing"
                                    required
                                />
                                <InputError :message="form.errors.grid_cols" />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">Số hàng và số cột xác định kích thước sơ đồ bố trí thiết bị của phòng.</p>
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex w-full items-center justify-between sm:justify-end sm:space-x-2">
                        <Button type="button" variant="outline" @click="isOpen = false" :disabled="form.processing"> Hủy </Button>
                        <Button type="submit" :disabled="form.processing" class="relative">
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            <span>{{ isEdit ? 'Cập nhật' : 'Tạo phòng' }}</span>
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
