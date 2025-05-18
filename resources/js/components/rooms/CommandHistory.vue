<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CommandHistoryItem } from '@/types/commandHistory';
import axios from 'axios';
import { History, Loader2, RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    roomId: string;
}>();

const commandHistory = ref<CommandHistoryItem[]>([]);
const isLoading = ref<boolean>(false);
const isOpen = ref<boolean>(false);
const error = ref<string | null>(null);

// Format the date in a readable format
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('default', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(date);
};

// Get status badge color based on command status
const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-500';
        case 'sent':
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-500';
        case 'completed':
            return 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-500';
        case 'failed':
            return 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-500';
        default:
            return 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-900/30 dark:text-gray-500';
    }
};

// Load command history
const loadCommandHistory = async () => {
    if (!props.roomId) return;

    isLoading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/rooms/${props.roomId}/commands`);
        commandHistory.value = response.data.data;
    } catch (err) {
        console.error('Failed to load command history:', err);
        error.value = 'Failed to load command history. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

// Load data when dialog opens
const handleOpen = () => {
    isOpen.value = true;
    loadCommandHistory();
};

// External refresh method
const refreshHistory = () => {
    loadCommandHistory();
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger asChild @click="handleOpen">
            <Button variant="outline" size="sm" class="h-9 gap-1">
                <History class="h-4 w-4" />
                <span>Command History</span>
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Command History</DialogTitle>
                <DialogDescription> View all commands sent in this room </DialogDescription>
            </DialogHeader>

            <div class="mt-4 max-h-[60vh] overflow-y-auto pr-1">
                <div class="sticky top-0 z-10 mb-4 flex items-center justify-between bg-background pb-2">
                    <h3 class="text-sm font-medium text-muted-foreground">Recent commands</h3>
                    <Button variant="outline" size="sm" @click="refreshHistory" :disabled="isLoading">
                        <RefreshCw v-if="!isLoading" class="mr-1 h-4 w-4" />
                        <Loader2 v-else class="mr-1 h-4 w-4 animate-spin" />
                        Refresh
                    </Button>
                </div>

                <div v-if="error" class="py-4 text-center text-red-500">
                    {{ error }}
                </div>

                <div v-else-if="isLoading" class="py-8 text-center">
                    <Loader2 class="mx-auto h-8 w-8 animate-spin text-muted-foreground" />
                    <p class="mt-2 text-sm text-muted-foreground">Loading command history...</p>
                </div>

                <div v-else-if="commandHistory.length === 0" class="py-8 text-center">
                    <p class="text-muted-foreground">No commands have been sent in this room yet.</p>
                </div>

                <div v-else class="rounded-md border">
                    <Table class="w-full text-sm">
                        <TableHeader>
                            <TableRow class="border-b hover:bg-transparent">
                                <TableHead class="w-1/6 py-2">Type</TableHead>
                                <TableHead class="w-1/6 py-2">Target</TableHead>
                                <TableHead class="w-1/6 py-2">Status</TableHead>
                                <TableHead class="w-1/5 py-2">Sent at</TableHead>
                                <TableHead class="w-1/5 py-2">Completed</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="command in commandHistory" :key="command.id" class="border-b">
                                <TableCell class="max-w-[120px] truncate py-2" :title="command.type">{{ command.type }}</TableCell>
                                <TableCell class="py-2">
                                    <div class="flex items-center">
                                        <span class="max-w-[80px] truncate" :title="command.target">{{ command.target }}</span>
                                        <Badge v-if="command.is_group_command" variant="outline" class="ml-2">Group</Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="py-2">
                                    <Badge :class="getStatusColor(command.status)">
                                        {{ command.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="py-2">{{ formatDate(command.created_at) }}</TableCell>
                                <TableCell class="py-2">
                                    <span v-if="command.completed_at">{{ formatDate(command.completed_at) }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <DialogFooter class="flex items-center justify-between">
                <div class="text-xs text-muted-foreground">{{ commandHistory.length }} commands found</div>
                <DialogClose asChild>
                    <Button variant="outline">Close</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
