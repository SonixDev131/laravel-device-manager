<script setup lang="ts">
// 1. Imports
import { Input } from '@/components/ui/input';
import { useDebounceFn } from '@vueuse/core';
import { Loader2, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

// 2. Props/emits
interface Props {
    initialQuery?: string;
}

const props = withDefaults(defineProps<Props>(), {
    initialQuery: '',
});

const emit = defineEmits<{
    (e: 'update:query', value: string): void;
}>();

// 3. Reactive state
const searchQuery = ref<string>(props.initialQuery);
const isSearching = ref<boolean>(false);

// 4. Methods
watch(searchQuery, (newValue: string): void => {
    isSearching.value = true;
    debouncedSearch(newValue);
});

const debouncedSearch = useDebounceFn((value: string): void => {
    emit('update:query', value);
    isSearching.value = false;
}, 300);
</script>

<template>
    <div class="relative w-full max-w-sm">
        <Input id="search" v-model="searchQuery" type="text" placeholder="Tìm phòng theo tên..." class="pl-10" />
        <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
            <Search v-if="!isSearching" class="size-5 text-muted-foreground" />
            <Loader2 v-else class="size-5 animate-spin text-muted-foreground" />
        </span>
    </div>
</template>
