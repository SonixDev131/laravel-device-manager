<script setup lang="ts">
import { Computer } from '@/types';

defineEmits<{
    click: [];
}>();

interface Props {
    computer: Computer;
    index: number;
    isSelected: boolean;
    isSelectable?: boolean;
}

const { isSelectable = true } = defineProps<Props>();
</script>

<template>
    <div
        class="flex h-16 w-16 select-none items-center justify-center rounded border-2 transition-all duration-200"
        :class="{
            'border-primary bg-primary/10': isSelected,
            'border-gray-200 hover:border-gray-300 hover:bg-muted/10': !isSelected && isSelectable,
            'border-gray-100 opacity-70': !isSelectable,
            'cursor-pointer': isSelectable,
            'cursor-not-allowed': !isSelectable,
        }"
        @click="$emit('click')"
    >
        <span class="text-sm font-medium">{{ computer.name }}</span>
    </div>
</template>
