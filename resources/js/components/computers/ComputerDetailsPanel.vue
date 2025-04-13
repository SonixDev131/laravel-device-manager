<script setup lang="ts">
import ComputerProperties from '@/components/computers/ComputerProperties.vue';
import QuickActionsSection from '@/components/computers/QuickActionsSection.vue';
import ResourceMetricsSection from '@/components/computers/ResourceMetricsSection.vue';
import StatusBadge from '@/components/computers/StatusBadge.vue';
import SystemInfoSection from '@/components/computers/SystemInfoSection.vue';
import { Button } from '@/components/ui/button';
import type { Computer } from '@/types';
import { Monitor, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    computer: Computer | null;
    isOpen: boolean;
    multipleSelection: boolean;
    selectionCount: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    'execute-command': [commandType: string];
}>();

// Get computer name with fallback
const computerName = computed<string>(() => {
    if (!props.computer) return '';
    return props.computer.system_metrics?.hostname || props.computer.name || 'Unknown Computer';
});

// Handle close button click
const closePanel = (): void => {
    emit('close');
};

// Handle command execution
const executeCommand = (commandType: string): void => {
    emit('execute-command', commandType);
};
</script>

<template>
    <div class="flex w-full flex-col overflow-hidden rounded-lg bg-white shadow-lg dark:bg-gray-800 md:w-80 lg:w-96">
        <!-- Panel Header -->
        <div class="flex items-center justify-between border-b bg-gray-50 p-3 dark:bg-gray-700">
            <div class="flex items-center gap-2">
                <Monitor class="h-5 w-5 text-primary" />
                <h3 class="font-medium">Computer Details</h3>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" @click="closePanel">
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Panel Content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Single Computer Details -->
            <div v-if="computer" class="space-y-6">
                <!-- Computer Header -->
                <div class="flex items-center justify-between">
                    <h4 class="truncate text-lg font-semibold" :title="computerName">
                        {{ computerName }}
                    </h4>
                    <StatusBadge :status="computer.status" />
                </div>

                <!-- Computer Properties -->
                <ComputerProperties :computer="computer" />

                <!-- System Info Section -->
                <SystemInfoSection :metrics="computer.system_metrics" />

                <!-- Resource Metrics Section -->
                <ResourceMetricsSection :metrics="computer.system_metrics" />

                <!-- Quick Actions Section -->
                <QuickActionsSection :computer="computer" @execute-command="executeCommand" />
            </div>

            <!-- Multiple Selection Message -->
            <div v-else-if="multipleSelection" class="py-8 text-center">
                <h4 class="mb-2 text-lg font-medium">Multiple Computers Selected</h4>
                <p class="mb-4 text-gray-500 dark:text-gray-400">{{ selectionCount }} computers are currently selected</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Select a single computer to view its details</p>
            </div>

            <!-- No Selection Message -->
            <div v-else class="py-8 text-center">
                <h4 class="mb-2 text-lg font-medium">No Computer Selected</h4>
                <p class="text-gray-500 dark:text-gray-400">Select a computer to view its details</p>
            </div>
        </div>
    </div>
</template>
