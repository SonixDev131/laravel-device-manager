<script setup lang="ts">
// 1. Imports
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

// 2. Props & Emits
interface Props {
    roomId: string;
    isOpen?: boolean;
}

const emit = defineEmits<{
    close: [];
}>();

const props = withDefaults(defineProps<Props>(), {
    isOpen: false,
});

// 3. Reactive State
const open = ref(props.isOpen);
const responseMessage = ref('');
const isSuccess = ref(false);
const isLoading = ref(false);

const form = useForm({
    version: '',
    force: false,
    restart_after: true,
});

// 4. Methods
const handleSubmit = async () => {
    isLoading.value = true;
    responseMessage.value = '';

    try {
        const response = await axios.post(`/rooms/${props.roomId}/update-agents`, form.data());
        isSuccess.value = response.data.success;
        responseMessage.value = response.data.message;

        if (isSuccess.value) {
            // Reset form after successful submission
            form.reset();
            // Close dialog after 3 seconds
            setTimeout(() => {
                closeDialog();
            }, 3000);
        }
    } catch (error: any) {
        isSuccess.value = false;
        responseMessage.value = error.response?.data?.message || 'An error occurred while updating agents';
    } finally {
        isLoading.value = false;
    }
};

const closeDialog = () => {
    open.value = false;
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogTrigger as-child>
            <Button variant="default" class="bg-blue-600 hover:bg-blue-700"> Update All Agents </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Update All Agents</DialogTitle>
                <DialogDescription> Update all agents in this room. This will send update commands to all online computers. </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <div class="space-y-2">
                    <Label for="version">Version (optional)</Label>
                    <Input id="version" v-model="form.version" placeholder="Leave blank for latest version" />
                    <p class="text-xs text-gray-500">If blank, agents will update to the latest available version</p>
                </div>

                <div class="flex items-center space-x-2">
                    <Switch id="force-update" v-model:checked="form.force" />
                    <Label for="force-update">Force update (ignores version check)</Label>
                </div>

                <div class="flex items-center space-x-2">
                    <Switch id="restart-after" v-model:checked="form.restart_after" />
                    <Label for="restart-after">Restart after update</Label>
                </div>

                <Alert v-if="responseMessage" :class="isSuccess ? 'bg-green-50' : 'bg-red-50'">
                    <AlertDescription>{{ responseMessage }}</AlertDescription>
                </Alert>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeDialog" :disabled="isLoading"> Cancel </Button>
                    <Button type="submit" :disabled="isLoading">
                        <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isLoading ? 'Updating...' : 'Update Agents' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
