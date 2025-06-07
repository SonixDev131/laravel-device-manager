<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Computer } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    formId: string;
    computer: Computer | null;
    roomId: string;
}

const props = defineProps<Props>();
const isOpen = defineModel('isOpen', { default: false });

const emit = defineEmits<{
    submit: [data: any];
    close: [];
}>();

// Initialize form with computer data
const form = useForm({
    hostname: '',
    mac_address: '',
    pos_row: 1,
    pos_col: 1,
});

// Watch for computer changes to populate form
watch(
    () => props.computer,
    (newComputer) => {
        if (newComputer && isOpen.value) {
            form.hostname = newComputer.hostname || '';
            form.mac_address = newComputer.mac_address || '';
            form.pos_row = newComputer.pos_row;
            form.pos_col = newComputer.pos_col;
        }
    },
    { immediate: true, deep: true },
);

const onSubmit = () => {
    if (!props.computer) return;

    form.put(route('rooms.computers.update', [props.roomId, props.computer.id]), {
        onSuccess: () => {
            isOpen.value = false;
            emit('submit', form);
        },
        preserveScroll: true,
    });
};

const suggestedName = computed(() => {
    if (!props.computer) return '';
    return `PC-R${props.computer.pos_row}-C${props.computer.pos_col}`;
});
</script>

<template>
    <Dialog :open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Edit Computer</DialogTitle>
                <DialogDescription v-if="computer">
                    Update computer details for position ({{ computer.pos_row }}, {{ computer.pos_col }})
                </DialogDescription>
            </DialogHeader>

            <form :id="formId" @submit.prevent="onSubmit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <!-- MAC Address Field -->
                    <div class="grid gap-2">
                        <Label for="edit_mac_address" class="flex items-center">
                            <span>MAC Address</span>
                            <span class="ml-1 text-xs text-red-500">*</span>
                        </Label>
                        <Input
                            id="edit_mac_address"
                            type="text"
                            placeholder="00:00:00:00:00:00"
                            v-model="form.mac_address"
                            :disabled="form.processing"
                            required
                        />
                        <InputError :message="form.errors.mac_address" />
                    </div>

                    <!-- Computer Name -->
                    <div class="grid gap-2">
                        <Label for="edit_hostname" class="flex items-center">
                            <span>Computer Name</span>
                            <span class="ml-1 text-xs text-muted-foreground">(Optional)</span>
                        </Label>
                        <Input id="edit_hostname" type="text" :placeholder="suggestedName" v-model="form.hostname" :disabled="form.processing" />
                        <div class="text-xs text-muted-foreground">Leave empty to use "{{ suggestedName }}"</div>
                        <InputError :message="form.errors.hostname" />
                    </div>

                    <!-- Position fields (read-only for now) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit_pos_row">Row Position</Label>
                            <Input id="edit_pos_row" type="number" v-model="form.pos_row" :disabled="true" class="bg-muted" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit_pos_col">Column Position</Label>
                            <Input id="edit_pos_col" type="number" v-model="form.pos_col" :disabled="true" class="bg-muted" />
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex w-full items-center justify-between sm:justify-end sm:space-x-2">
                        <Button type="button" variant="outline" @click="isOpen = false" :disabled="form.processing"> Cancel </Button>
                        <Button type="submit" :disabled="form.processing" class="relative">
                            <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            <span>Update Computer</span>
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
