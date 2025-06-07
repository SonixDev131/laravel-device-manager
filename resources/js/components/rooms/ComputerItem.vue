<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Computer } from '@/types';
import { Edit, Trash } from 'lucide-vue-next';
import { computed } from 'vue';

defineEmits<{
    click: [];
    editComputer: [computer: Computer];
    deleteComputer: [computer: Computer];
}>();

interface Props {
    computer: Computer;
    index: number;
    isSelected: boolean;
    isSelectable?: boolean;
    canManageComputers?: boolean;
}

const props = defineProps<Props>();
const { isSelectable = true, canManageComputers = false } = props;

// Status indicators computed properties
const statusDotClass = computed(() => {
    const baseClasses = ['status-dot', 'h-2', 'w-2', 'rounded-full', 'inline-block', 'mr-1.5'];

    if (props.computer.status == 'online') {
        baseClasses.push('bg-green-500', 'animate-pulse');
    } else {
        baseClasses.push('bg-gray-300');
    }

    return baseClasses.join(' ');
});

const statusText = computed(() => {
    return props.computer.status == 'online' ? 'Online' : 'Offline';
});

const statusTextClass = computed(() => {
    const baseClasses = ['status-text', 'text-xs', 'font-medium'];

    if (props.computer.status == 'online') {
        baseClasses.push('text-green-600');
    } else {
        baseClasses.push('text-gray-500');
    }

    return baseClasses.join(' ');
});

// Format uptime in a human-readable way (e.g., "2 days 3 hours")
const formattedUptime = computed(() => {
    const uptime = props.computer.latest_metric?.uptime;
    if (!uptime) return 'Unknown';

    const days = Math.floor(uptime / 86400);
    const hours = Math.floor((uptime % 86400) / 3600);
    const minutes = Math.floor((uptime % 3600) / 60);

    if (days > 0) {
        return `${days}d ${hours}h`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
});

// Status ring class bindings
const statusRingClass = computed(() => {
    const baseClasses = ['status-ring', 'absolute', 'inset-0', 'rounded', 'ring-4', 'ring-inset', 'transition-colors'];

    if (props.computer.status == 'online') {
        baseClasses.push('ring-green-500/50');
    } else {
        baseClasses.push('ring-gray-300/30');
    }

    return baseClasses.join(' ');
});

// Class binding for the main computer item
const computerClass = computed(() => {
    const baseClasses = [
        'group', // Add group class for hover effects
        'relative',
        'flex',
        'h-16', // Reduced height since status is now outside
        'w-16',
        'select-none',
        'flex-col',
        'items-center',
        'justify-center',
        'rounded',
        'border',
        'transition-all',
        'duration-200',
        'p-1',
    ];

    // Selection states
    if (props.isSelected) {
        baseClasses.push('border-primary', 'bg-primary/10');
    } else if (isSelectable) {
        baseClasses.push('hover:border-gray-300', 'hover:bg-muted/10');
    } else {
        baseClasses.push('opacity-70');
    }

    // Cursor styles
    baseClasses.push(isSelectable ? 'cursor-pointer' : 'cursor-not-allowed');

    return baseClasses.join(' ');
});

const firewallProfiles = [
    { key: 'Domain', label: 'Domain' },
    { key: 'Private', label: 'Private' },
    { key: 'Public', label: 'Public' },
];

const firewallStatus = computed(() => {
    // Parse the JSON string if it's a string
    const rawFirewallStatus = props.computer.latest_metric?.firewall_status;

    // If it's a string, parse it to an object
    const parsedFirewallStatus = typeof rawFirewallStatus === 'string' ? JSON.parse(rawFirewallStatus) : rawFirewallStatus;

    return firewallProfiles.map((profile) => {
        // Now use the parsed object
        const value = parsedFirewallStatus?.[profile.key];

        return {
            ...profile,
            value,
            color: value === 'ON' ? 'bg-green-500' : value === 'OFF' ? 'bg-gray-300' : 'bg-yellow-400',
        };
    });
});
</script>

<template>
    <div class="flex flex-col items-center">
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger as-child>
                    <!-- Computer item (box) -->
                    <div :class="computerClass" @click="$emit('click')">
                        <!-- Status ring overlay (subtle background effect) -->
                        <div :class="statusRingClass"></div>

                        <!-- Admin action buttons (only visible on hover and with permission) -->
                        <div
                            v-if="canManageComputers"
                            class="absolute -right-1 -top-1 z-10 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <!-- Edit Button -->
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-6 w-6 bg-white p-0 shadow-sm hover:bg-blue-50 hover:shadow-md"
                                @click.stop="$emit('editComputer', computer)"
                            >
                                <Edit class="h-3 w-3 text-blue-600" />
                            </Button>

                            <!-- Delete Button -->
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-6 w-6 bg-white p-0 shadow-sm hover:bg-red-50 hover:shadow-md"
                                @click.stop="$emit('deleteComputer', computer)"
                            >
                                <Trash class="h-3 w-3 text-red-600" />
                            </Button>
                        </div>

                        <!-- Computer content -->
                        <!-- <div class="z-10 flex items-center justify-center">
                            <span class="max-w-full truncate text-sm font-medium">{{ computer.hostname }}</span>
                        </div> -->
                    </div>
                </TooltipTrigger>
                <TooltipContent class="w-68 p-0" side="right" v-if="computer.status == 'online'">
                    <div class="rounded-md border bg-card text-card-foreground shadow-sm">
                        <div class="flex flex-col gap-2 p-3">
                            <!-- Header with computer name -->
                            <div class="flex items-center justify-between border-b pb-1">
                                <h4 class="text-sm font-semibold">{{ computer.hostname }}</h4>
                                <span class="text-xs text-muted-foreground">
                                    {{ computer.mac_address || 'No MAC' }}
                                </span>
                            </div>

                            <!-- Metrics section -->
                            <div class="space-y-2">
                                <!-- CPU Usage -->
                                <div v-if="computer.latest_metric?.cpu_usage !== undefined" class="grid grid-cols-[1fr_auto] items-center gap-2">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium">CPU</span>
                                            <span class="text-xs text-muted-foreground">{{ `${computer.latest_metric?.cpu_usage} %` }}</span>
                                        </div>
                                        <Progress class="h-1.5" :model-value="computer.latest_metric?.cpu_usage" />
                                    </div>
                                </div>

                                <!-- Memory Usage -->
                                <div v-if="computer.latest_metric?.memory_used !== undefined" class="grid grid-cols-[1fr_auto] items-center gap-2">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium">Memory</span>
                                            <span class="text-xs text-muted-foreground">{{
                                                `${((computer.latest_metric?.memory_used / computer.latest_metric?.memory_total) * 100).toFixed(1)} %`
                                            }}</span>
                                        </div>
                                        <Progress
                                            class="h-1.5"
                                            :model-value="
                                                Number(
                                                    ((computer.latest_metric?.memory_used / computer.latest_metric?.memory_total) * 100).toFixed(1),
                                                )
                                            "
                                        />
                                    </div>
                                </div>

                                <!-- Disk Usage -->
                                <div v-if="computer.latest_metric?.disk_used !== undefined" class="grid grid-cols-[1fr_auto] items-center gap-2">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium">Disk</span>
                                            <span class="text-xs text-muted-foreground">{{
                                                `${((computer.latest_metric?.disk_used / computer.latest_metric?.disk_total) * 100).toFixed(1)} %`
                                            }}</span>
                                        </div>
                                        <Progress
                                            class="h-1.5"
                                            :model-value="
                                                Number(((computer.latest_metric?.disk_used / computer.latest_metric?.disk_total) * 100).toFixed(1))
                                            "
                                        />
                                    </div>
                                </div>

                                <!-- Uptime + Platform -->
                                <div class="mt-1 flex items-center justify-between border-t pt-1 text-xs">
                                    <span class="text-muted-foreground">Uptime: {{ formattedUptime }}</span>
                                    <span class="font-medium">{{ computer.latest_metric?.platform || 'Unknown OS' }}</span>
                                </div>

                                <!-- Firewall Status -->
                                <div v-if="computer.latest_metric?.firewall_status" class="mt-2 border-t pt-2">
                                    <div class="mb-1 text-xs font-medium text-muted-foreground">Firewall Status</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="profile in firewallStatus" :key="profile.key" class="flex items-center gap-1">
                                            <span class="inline-block h-2 w-2 rounded-full" :class="profile.color"></span>
                                            <span class="text-xs">{{ profile.label }}</span>
                                            <span class="text-xs font-semibold" :class="profile.value === 'ON' ? 'text-green-600' : 'text-gray-500'">
                                                {{ profile.value || 'Unknown' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <!-- Status indicator outside and below the computer box -->
        <div class="mt-1 flex items-center justify-center">
            <span :class="statusDotClass"></span>
            <span :class="statusTextClass">{{ statusText }}</span>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
