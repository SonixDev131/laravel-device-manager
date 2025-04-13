<script setup lang="ts">
import { Progress } from '@/components/ui/progress';
import type { Computer } from '@/types';
import { computed } from 'vue';

interface Props {
    computer: Computer;
}

const props = defineProps<Props>();

// Check if system metrics are available
const hasMetrics = computed<boolean>(() => {
    return !!props.computer.system_metrics && Object.keys(props.computer.system_metrics).length > 0;
});

// Format system metrics for display
const cpuUsage = computed<number | null>(() => {
    return props.computer.system_metrics?.cpu_usage ?? null;
});

const memoryUsage = computed<number | null>(() => {
    return props.computer.system_metrics?.memory_usage ?? null;
});

const diskUsage = computed<number | null>(() => {
    return props.computer.system_metrics?.disk_usage ?? null;
});

const platform = computed<string>(() => {
    const platform = props.computer.system_metrics?.platform;
    const version = props.computer.system_metrics?.platform_version;

    if (!platform) return 'Unknown';
    if (!version) return platform;

    return `${platform} ${version}`;
});

const hostname = computed<string>(() => {
    return props.computer.system_metrics?.hostname || props.computer.name || 'Unknown';
});

const uptime = computed<string | null>(() => {
    if (!props.computer.system_metrics?.uptime) return null;

    const seconds = props.computer.system_metrics.uptime;
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    const parts = [];
    if (days > 0) parts.push(`${days}d`);
    if (hours > 0 || days > 0) parts.push(`${hours}h`);
    if (minutes > 0 || hours > 0 || days > 0) parts.push(`${minutes}m`);

    return parts.length > 0 ? parts.join(' ') : '< 1m';
});

// Get color classes based on usage percentage
const getUsageColor = (value: number | null): string => {
    if (value === null) return 'bg-gray-200';

    if (value < 50) return 'bg-green-500';
    if (value < 80) return 'bg-yellow-500';
    return 'bg-red-500';
};

const lastSeen = computed<string | null>(() => {
    return props.computer.last_seen_at ? new Date(props.computer.last_seen_at).toLocaleString() : null;
});
</script>

<template>
    <div class="w-full rounded-lg bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-lg font-medium">{{ hostname }}</h3>

        <div v-if="hasMetrics" class="space-y-4">
            <!-- System Information -->
            <div class="flex flex-col gap-1 text-sm text-gray-600">
                <div v-if="platform"><span class="font-medium">OS:</span> {{ platform }}</div>
                <div v-if="uptime"><span class="font-medium">Uptime:</span> {{ uptime }}</div>
                <div v-if="lastSeen"><span class="font-medium">Last Seen:</span> {{ lastSeen }}</div>
            </div>

            <!-- Resource Usage -->
            <div class="space-y-3">
                <!-- CPU Usage -->
                <div v-if="cpuUsage !== null">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-sm font-medium">CPU</span>
                        <span class="text-sm">{{ cpuUsage }}%</span>
                    </div>
                    <Progress :model-value="cpuUsage" :class="getUsageColor(cpuUsage)" />
                </div>

                <!-- Memory Usage -->
                <div v-if="memoryUsage !== null">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-sm font-medium">Memory</span>
                        <span class="text-sm">{{ memoryUsage }}%</span>
                    </div>
                    <Progress :model-value="memoryUsage" :class="getUsageColor(memoryUsage)" />
                </div>

                <!-- Disk Usage -->
                <div v-if="diskUsage !== null">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-sm font-medium">Disk</span>
                        <span class="text-sm">{{ diskUsage }}%</span>
                    </div>
                    <Progress :model-value="diskUsage" :class="getUsageColor(diskUsage)" />
                </div>
            </div>
        </div>

        <div v-else class="py-2 text-sm italic text-gray-500">No system metrics available</div>
    </div>
</template>
