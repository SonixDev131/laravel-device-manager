<script setup lang="ts">
import type { Computer } from '@/types';
import { computed } from 'vue';

interface Props {
    computer: Computer;
}

const props = defineProps<Props>();

// Format the last seen time as a localized string
const formattedLastSeen = computed<string | null>(() => {
    if (!props.computer.last_seen_at) return null;
    return new Date(props.computer.last_seen_at).toLocaleString();
});

// Get computer name/hostname with fallback
const computerName = computed<string>(() => {
    return props.computer.system_metrics?.hostname || props.computer.name || 'Unknown Computer';
});
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div class="text-gray-500 dark:text-gray-400">Position</div>
            <div>Row {{ computer.pos_row }}, Column {{ computer.pos_col }}</div>

            <div class="text-gray-500 dark:text-gray-400">MAC Address</div>
            <div class="font-mono text-xs">{{ computer.mac_address }}</div>

            <div class="text-gray-500 dark:text-gray-400">IP Address</div>
            <div class="font-mono text-xs">{{ computer.ip_address || 'Not available' }}</div>

            <template v-if="formattedLastSeen">
                <div class="text-gray-500 dark:text-gray-400">Last Seen</div>
                <div>{{ formattedLastSeen }}</div>
            </template>
        </div>
    </div>
</template>
