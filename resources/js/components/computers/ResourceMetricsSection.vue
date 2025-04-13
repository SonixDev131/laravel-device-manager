<script setup lang="ts">
import ResourceUsageBar from '@/components/computers/ResourceUsageBar.vue';
import type { SystemMetrics } from '@/types';
import { computed } from 'vue';

interface Props {
    metrics: SystemMetrics | undefined;
}

const props = defineProps<Props>();

// Check if any resource metrics are available
const hasResourceMetrics = computed<boolean>(() => {
    return Boolean(props.metrics?.cpu_usage !== undefined || props.metrics?.memory_usage !== undefined || props.metrics?.disk_usage !== undefined);
});
</script>

<template>
    <div class="space-y-4">
        <h5 class="font-medium">Resource Usage</h5>

        <div v-if="hasResourceMetrics" class="space-y-3">
            <ResourceUsageBar v-if="metrics?.cpu_usage !== undefined" :value="metrics.cpu_usage" label="CPU" />

            <ResourceUsageBar v-if="metrics?.memory_usage !== undefined" :value="metrics.memory_usage" label="Memory" />

            <ResourceUsageBar v-if="metrics?.disk_usage !== undefined" :value="metrics.disk_usage" label="Disk" />
        </div>

        <div v-else class="text-sm italic text-gray-500">No resource metrics available</div>
    </div>
</template>
