<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { watch } from 'vue';

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

// Initialize form with proper typing
const form = useForm({
    name: '',
    ip_address: '',
    mac_address: '',
    status: 'available',
    type: 'desktop',
    room_id: props.roomId,
    pos_row: props.position.row,
    pos_col: props.position.col,
});

// Reset form when dialog closes
const resetForm = () => {
    form.name = '';
    form.ip_address = '';
    form.mac_address = '';
    form.status = 'available';
    form.type = 'desktop';
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
    form.post(route('computers.store', props.roomId), {
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
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Add New Computer</DialogTitle>
                <DialogDescription> Enter computer details for position ({{ position.row }}, {{ position.col }}) </DialogDescription>
            </DialogHeader>

            <form :id="formId" @submit.prevent="onSubmit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- Computer Name Field -->
                    <div class="grid gap-2">
                        <Label for="name">Computer Name</Label>
                        <Input id="name" type="text" placeholder="Enter computer name..." v-model="form.name" :disabled="form.processing" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- IP Address Field
                    <div class="grid gap-2">
                        <Label for="ip_address">IP Address</Label>
                        <Input id="ip_address" type="text" placeholder="192.168.1.1" v-model="form.ip_address" :disabled="form.processing" />
                        <InputError :message="form.errors.ip_address" />
                    </div> -->

                    <!-- MAC Address Field -->
                    <div class="grid gap-2">
                        <Label for="mac_address">MAC Address</Label>
                        <Input id="mac_address" type="text" placeholder="00:00:00:00:00:00" v-model="form.mac_address" :disabled="form.processing" />
                        <InputError :message="form.errors.mac_address" />
                    </div>

                    <!-- <div class="grid grid-cols-2 gap-4"> -->
                    <!-- Type Field -->
                    <!-- <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <Select v-model="form.type" :disabled="form.processing">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="desktop">Desktop</SelectItem>
                                    <SelectItem value="laptop">Laptop</SelectItem>
                                    <SelectItem value="server">Server</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" />
                        </div> -->

                    <!-- Status Field
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status" :disabled="form.processing">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="available">Available</SelectItem>
                                    <SelectItem value="in_use">In Use</SelectItem>
                                    <SelectItem value="maintenance">Maintenance</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div> -->
                    <!-- </div> -->
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
