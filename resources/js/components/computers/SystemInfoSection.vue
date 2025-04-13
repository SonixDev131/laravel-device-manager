<script setup lang="ts">
import type { SystemMetrics } from '@/types';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    metrics: SystemMetrics | undefined;
}

const props = defineProps<Props>();

// Format uptime in a human-readable format
const formattedUptime = computed<string>(() => {
    if (props.metrics?.uptime === undefined) return 'Unknown';

    const seconds = props.metrics.uptime;
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    const parts = [];
    if (days > 0) parts.push(`${days}d`);
    if (hours > 0 || days > 0) parts.push(`${hours}h`);
    if (minutes > 0 || hours > 0 || days > 0) parts.push(`${minutes}m`);

    return parts.length > 0 ? parts.join(' ') : '< 1m';
});

// Format platform information
const platformInfo = computed<string>(() => {
    if (!props.metrics?.platform) return 'Unknown';

    return props.metrics.platform_version ? `${props.metrics.platform} ${props.metrics.platform_version}` : props.metrics.platform;
});

// Check if any system information is available
const hasSystemInfo = computed<boolean>(() => {
    return Boolean(props.metrics?.platform || props.metrics?.uptime !== undefined);
});
</script>

<template>
    <div class="space-y-4">
        <h5 class="flex items-center gap-1 font-medium">
            <Info class="h-4 w-4" />
            System Information
        </h5>

        <div v-if="hasSystemInfo" class="grid grid-cols-2 gap-2 text-sm">
            <template v-if="metrics?.platform">
                <div class="text-gray-500 dark:text-gray-400">Platform</div>
                <div>{{ platformInfo }}</div>
            </template>

            <template v-if="metrics?.uptime !== undefined">
                <div class="text-gray-500 dark:text-gray-400">Uptime</div>
                <div>{{ formattedUptime }}</div>
            </template>
        </div>

        <div v-else class="text-sm italic text-gray-500">No system information available</div>
    </div>
</template>
