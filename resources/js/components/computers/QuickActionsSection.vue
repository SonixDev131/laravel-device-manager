<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Computer } from '@/types';

interface Props {
    computer: Computer;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'execute-command': [commandType: string];
}>();

// Command execution handlers
const executeStatusRequest = (): void => {
    emit('execute-command', 'status_request');
};

const executeRestart = (): void => {
    emit('execute-command', 'restart');
};

const executeShutdown = (): void => {
    emit('execute-command', 'shutdown');
};

const executeMaintenance = (): void => {
    emit('execute-command', 'maintenance');
};
</script>

<template>
    <div class="space-y-3">
        <h5 class="font-medium">Quick Actions</h5>
        <div class="grid grid-cols-2 gap-2">
            <Button @click="executeStatusRequest" variant="outline" size="sm" class="flex items-center gap-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path>
                    <path d="M21 3v5h-5"></path>
                </svg>
                Refresh Status
            </Button>

            <Button @click="executeRestart" variant="outline" size="sm" class="flex items-center gap-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M21 2v6h-6"></path>
                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                    <path d="M3 22v-6h6"></path>
                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                </svg>
                Restart
            </Button>

            <Button @click="executeShutdown" variant="outline" size="sm" class="flex items-center gap-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                    <line x1="12" y1="2" x2="12" y2="12"></line>
                </svg>
                Shutdown
            </Button>

            <Button variant="outline" size="sm" disabled class="flex items-center gap-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                    <path d="M19.45 14.795a2 2 0 0 0 0-5.59"></path>
                    <path d="M4.55 9.205a2 2 0 0 0 0 5.59"></path>
                    <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"></path>
                </svg>
                Maintenance
            </Button>
        </div>
    </div>
</template>
