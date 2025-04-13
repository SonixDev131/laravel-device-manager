<script setup lang="ts">
import { ComputerStatus } from '@/types';

interface Props {
    status: ComputerStatus;
}

const props = defineProps<Props>();

// Get display label for a status
const statusLabel = computed<string>(() => {
    const labels = {
        [ComputerStatus.ONLINE]: 'Online',
        [ComputerStatus.OFFLINE]: 'Offline',
        [ComputerStatus.IDLE]: 'Idle',
        [ComputerStatus.MAINTENANCE]: 'Maintenance',
        [ComputerStatus.SHUTTING_DOWN]: 'Shutting Down',
    };

    return labels[props.status] || 'Unknown';
});

// Get color classes for the badge
const statusColorClasses = computed<string>(() => {
    return (
        {
            [ComputerStatus.ONLINE]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
            [ComputerStatus.OFFLINE]: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
            [ComputerStatus.IDLE]: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
            [ComputerStatus.MAINTENANCE]: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
            [ComputerStatus.SHUTTING_DOWN]: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
        }[props.status] || 'bg-gray-100 text-gray-800'
    );
});
</script>

<template>
    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColorClasses">
        {{ statusLabel }}
    </span>
</template>
