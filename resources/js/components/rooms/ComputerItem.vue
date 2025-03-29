<script setup lang="ts">
import { Computer } from '@/types';

defineEmits<{
    click: [];
}>();

interface Props {
    computer: Computer;
    index: number;
    isSelected: boolean;
}

defineProps<Props>();
</script>

<template>
    <div
        class="flex h-16 w-16 cursor-pointer select-none items-center justify-center rounded border-2"
        :class="{
            'border-primary bg-primary/10': isSelected,
            'border-gray-200': !isSelected,
        }"
        @click="$emit('click')"
    >
        <span class="text-sm font-medium">{{ computer.name }}</span>
    </div>
</template>
