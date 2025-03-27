<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Room, RoomFormData } from '@/types';
import { toTypedSchema } from '@vee-validate/zod';
import { Loader2 } from 'lucide-vue-next';
import { useForm } from 'vee-validate';
import { ref, watch } from 'vue';
import * as z from 'zod';

interface Props {
    formId: string;
    isEdit: boolean;
    isOpen: boolean;
    room?: Partial<Room>;
    showTrigger?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
    isEdit: false,
    room: () => ({
        name: '',
        grid_rows: 5,
        grid_cols: 5,
        type: 'lab',
        status: true,
    }),
    showTrigger: false,
});

const emit = defineEmits<{
    submit: [value: RoomFormData];
    close: [];
    'update:isOpen': [value: boolean];
}>();

// Form validation schema
const formSchema = toTypedSchema(
    z.object({
        name: z.string().trim().min(2, 'Tên phòng phải có ít nhất 2 ký tự').max(50, 'Tên phòng không được quá 50 ký tự'),
        grid_rows: z.number().int().min(1, 'Số hàng phải lớn hơn 0').max(20, 'Số hàng không được vượt quá 20'),
        grid_cols: z.number().int().min(1, 'Số cột phải lớn hơn 0').max(20, 'Số cột không được vượt quá 20'),
        type: z.string().min(1, 'Vui lòng chọn loại phòng'),
        status: z.boolean().default(true),
    }),
);

// Form submission state
const isSubmitting = ref(false);

// Initialize form with validation
const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        name: props.room?.name || '',
        grid_rows: props.room?.grid_rows || 5,
        grid_cols: props.room?.grid_cols || 5,
        type: props.room?.type || 'lab',
        status: props.room?.status !== undefined ? props.room.status : true,
    },
});

// Reset form values whenever the dialog opens or room data changes
watch(
    [() => props.isOpen, () => props.room],
    ([isOpen, room]) => {
        if (isOpen) {
            form.resetForm({
                values: {
                    name: room?.name || '',
                    grid_rows: room?.grid_rows || 5,
                    grid_cols: room?.grid_cols || 5,
                    type: room?.type || 'lab',
                    status: room?.status !== undefined ? room.status : true,
                },
            });
        }
    },
    { immediate: true },
);

// Handle form submission
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isSubmitting.value = true;

        const formData: RoomFormData = {
            ...values,
            // Convert string numbers to actual numbers for grid dimensions
            grid_rows: Number(values.grid_rows),
            grid_cols: Number(values.grid_cols),
        };

        // Include ID if in edit mode
        if (props.isEdit && props.room?.id) {
            formData.id = props.room.id;
        }

        // Emit submission event with form data
        emit('submit', formData);

        // Clear form after successful submission
        if (!props.isEdit) {
            form.resetForm();
        }
    } catch (error) {
        console.error('Error submitting form:', error);
    } finally {
        isSubmitting.value = false;
    }
});

// Update dialog open state
const updateDialogState = (isOpen: boolean) => {
    emit('update:isOpen', isOpen);

    // If closing, also emit close event
    if (!isOpen) {
        emit('close');
    }
};

// Close dialog
const closeDialog = () => {
    emit('close');
    emit('update:isOpen', false);
};
</script>
<template>
    <Dialog :open="isOpen">
        <!-- <DialogTrigger v-if="!isOpen && showTrigger">
            <Button class="bg-blue-500 text-white hover:bg-blue-600" @click="updateDialogState(true)">
                {{ isEdit ? 'Chỉnh sửa' : 'Thêm Phòng' }}
            </Button>
        </DialogTrigger> -->

        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ isEdit ? 'Chỉnh Sửa Phòng' : 'Thêm Phòng Mới' }}</DialogTitle>
                <DialogDescription>
                    {{ isEdit ? 'Chỉnh sửa thông tin phòng' : 'Nhập thông tin phòng mới' }}
                    và nhấn Lưu khi hoàn tất.
                </DialogDescription>
            </DialogHeader>

            <form :id="formId" @submit.prevent="onSubmit" class="space-y-4">
                <!-- Room Name Field -->
                <FormField v-slot="{ componentField }" name="name">
                    <FormItem>
                        <FormLabel>Tên Phòng</FormLabel>
                        <FormControl>
                            <Input type="text" placeholder="Nhập tên phòng..." v-bind="componentField" :disabled="isSubmitting" />
                        </FormControl>
                        <FormDescription>Đây là tên hiển thị của phòng trong hệ thống.</FormDescription>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Room Type Field -->
                <!-- <FormField v-slot="{ componentField }" name="type">
                    <FormItem>
                        <FormLabel>Loại Phòng</FormLabel>
                        <FormControl>
                            <Select v-bind="componentField" :disabled="isSubmitting">
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn loại phòng" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="lab">Phòng thực hành</SelectItem>
                                    <SelectItem value="lecture">Phòng học</SelectItem>
                                    <SelectItem value="meeting">Phòng họp</SelectItem>
                                    <SelectItem value="office">Phòng làm việc</SelectItem>
                                </SelectContent>
                            </Select>
                        </FormControl>
                        <FormDescription>Chọn loại phòng phù hợp với mục đích sử dụng.</FormDescription>
                        <FormMessage />
                    </FormItem>
                </FormField> -->

                <div class="space-y-2">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Grid Rows Field -->
                        <FormField v-slot="{ componentField }" name="grid_rows">
                            <FormItem>
                                <FormLabel>Số hàng</FormLabel>
                                <FormControl>
                                    <Input type="number" placeholder="Nhập số hàng..." v-bind="componentField" :disabled="isSubmitting" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <!-- Grid Columns Field -->
                        <FormField v-slot="{ componentField }" name="grid_cols">
                            <FormItem>
                                <FormLabel>Số cột</FormLabel>
                                <FormControl>
                                    <Input type="number" placeholder="Nhập số cột..." v-bind="componentField" :disabled="isSubmitting" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <!-- Thay thế FormDescription bằng p element với styling tương tự -->
                    <p class="px-0.5 text-sm text-muted-foreground">Số hàng và số cột xác định kích thước sơ đồ bố trí thiết bị của phòng.</p>
                </div>

                <!-- Room Status Field -->
                <!-- <FormField v-slot="{ componentField }" name="status">
                    <FormItem class="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                        <FormControl>
                            <Checkbox v-bind="componentField" :disabled="isSubmitting" />
                        </FormControl>
                        <div class="space-y-1 leading-none">
                            <FormLabel>Hoạt động</FormLabel>
                            <FormDescription> Phòng có thể được sử dụng và hiển thị trong danh sách đặt phòng. </FormDescription>
                        </div>
                    </FormItem>
                </FormField> -->
            </form>

            <DialogFooter>
                <div class="flex w-full items-center justify-between sm:justify-end sm:space-x-2">
                    <Button type="button" variant="outline" @click="closeDialog" :disabled="isSubmitting"> Hủy </Button>
                    <Button type="submit" :form="formId" :disabled="isSubmitting || !form.meta.valid" class="relative">
                        <Loader2 v-if="isSubmitting" class="mr-2 size-4 animate-spin" />
                        <span>{{ isEdit ? 'Cập nhật' : 'Tạo phòng' }}</span>
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
