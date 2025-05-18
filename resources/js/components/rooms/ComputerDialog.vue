<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Position {
    row: number;
    col: number;
}

interface Props {
    formId: string;
    position: Position;
    roomId: string;
}

const props = defineProps<Props>();
const isOpen = defineModel('isOpen', { default: false });

const emit = defineEmits<{
    submit: [data: any];
    close: [];
}>();

// Tự động đề xuất tên dựa trên vị trí
const suggestedName = computed(() => {
    return `PC-R${props.position.row}-C${props.position.col}`;
});

// Initialize form with proper typing
const form = useForm({
    hostname: '',
    mac_address: '',
    status: 'operational',
    room_id: props.roomId,
    pos_row: props.position.row,
    pos_col: props.position.col,
});

// Reset form when dialog closes
const resetForm = () => {
    form.hostname = '';
    form.mac_address = '';
    form.status = 'operational';
    form.room_id = props.roomId;
    form.pos_row = props.position.row;
    form.pos_col = props.position.col;
};

// Watch for dialog open/close to reset form
watch(
    () => isOpen.value,
    (newValue) => {
        if (!newValue) {
            // Wait for transition to complete before resetting
            setTimeout(() => {
                if (!isOpen.value) {
                    resetForm();
                }
            }, 300);
        } else {
            // Update position values when dialog opens
            form.pos_row = props.position.row;
            form.pos_col = props.position.col;
            form.room_id = props.roomId;
        }
    },
);

// Watch for position changes to update the form
watch(
    () => props.position,
    (newPosition) => {
        if (isOpen.value) {
            form.pos_row = newPosition.row;
            form.pos_col = newPosition.col;
        }
    },
    { deep: true },
);

const onSubmit = () => {
    // Nếu tên trống, sử dụng tên đề xuất
    if (!form.hostname) {
        form.hostname = suggestedName.value;
    }

    form.post(route('rooms.computers.store', props.roomId), {
        onSuccess: () => {
            isOpen.value = false;
            emit('submit', form);
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Dialog :open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Add New Computer</DialogTitle>
                <DialogDescription> Enter computer details for position ({{ position.row }}, {{ position.col }}) </DialogDescription>
            </DialogHeader>

            <form :id="formId" @submit.prevent="onSubmit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- MAC Address Field - Bắt buộc -->
                    <div class="grid gap-2">
                        <Label for="mac_address" class="flex items-center">
                            <span>MAC Address</span>
                            <span class="ml-1 text-xs text-red-500">*</span>
                        </Label>
                        <Input
                            id="mac_address"
                            type="text"
                            placeholder="00:00:00:00:00:00"
                            v-model="form.mac_address"
                            :disabled="form.processing"
                            required
                        />
                        <InputError :message="form.errors.mac_address" />
                    </div>

                    <!-- Computer Name - Tự động đề xuất -->
                    <div class="grid gap-2">
                        <Label for="hostname" class="flex items-center">
                            <span>Computer Name</span>
                            <span class="ml-1 text-xs text-muted-foreground">(Optional)</span>
                        </Label>
                        <Input id="hostname" type="text" :placeholder="suggestedName" v-model="form.hostname" :disabled="form.processing" />
                        <div class="text-xs text-muted-foreground">Leave empty to use "{{ suggestedName }}"</div>
                        <InputError :message="form.errors.hostname" />
                    </div>

                    <!-- IP Address Field - Tùy chọn -->
                    <div class="grid gap-2">
                        <Label for="ip_address" class="flex items-center">
                            <span>IP Address</span>
                            <span class="ml-1 text-xs text-muted-foreground">(Optional)</span>
                        </Label>
                        <Input id="ip_address" type="text" placeholder="192.168.1.1" v-model="form.ip_address" :disabled="form.processing" />
                        <InputError :message="form.errors.ip_address" />
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex w-full items-center justify-between sm:justify-end sm:space-x-2">
                        <Button type="button" variant="outline" @click="isOpen = false" :disabled="form.processing"> Cancel </Button>
                        <Button type="submit" :disabled="form.processing" class="relative">
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            <span>Add Computer</span>
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
