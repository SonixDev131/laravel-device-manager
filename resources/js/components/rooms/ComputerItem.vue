<script setup lang="ts">
import { Computer } from '@/types';
import { computed } from 'vue';

defineEmits<{
    click: [];
}>();

interface Props {
    computer: Computer;
    index: number;
    isSelected: boolean;
    isSelectable?: boolean;
}

const props = defineProps<Props>();
const { isSelectable = true } = props;

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
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- Computer item (box) -->
        <div :class="computerClass" @click="$emit('click')">
            <!-- Status ring overlay (subtle background effect) -->
            <div :class="statusRingClass"></div>

            <!-- Computer content -->
            <div class="z-10 flex items-center justify-center">
                <span class="max-w-full truncate text-sm font-medium">{{ computer.name }}</span>
            </div>
        </div>

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
