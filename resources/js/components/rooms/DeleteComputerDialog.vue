<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Computer } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';

interface Props {
    computer: Computer | null;
    roomId: string;
}

const props = defineProps<Props>();
const isOpen = defineModel('isOpen', { default: false });

const emit = defineEmits<{
    confirm: [];
    close: [];
}>();

const form = useForm({});

const onConfirm = () => {
    if (!props.computer) return;

    form.delete(route('rooms.computers.destroy', [props.roomId, props.computer.id]), {
        onSuccess: () => {
            isOpen.value = false;
            emit('confirm');
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <Dialog :open="isOpen">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-red-500" />
                    Delete Computer
                </DialogTitle>
                <DialogDescription v-if="computer" class="pt-2">
                    Are you sure you want to delete
                    <strong>{{ computer.hostname || `Computer at (${computer.pos_row}, ${computer.pos_col})` }}</strong
                    >?
                    <br />
                    <br />
                    <span class="font-medium text-red-600">This action cannot be undone.</span> All associated data including metrics and command
                    history will be permanently removed.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button type="button" variant="outline" @click="isOpen = false" :disabled="form.processing"> Cancel </Button>
                <Button type="button" variant="destructive" @click="onConfirm" :disabled="form.processing">
                    <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                    <span>Delete Computer</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
