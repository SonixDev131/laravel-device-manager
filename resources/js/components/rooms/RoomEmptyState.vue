<script setup lang="ts">
// 1. Imports
import { Button } from '@/components/ui/button';
import { FolderOpen } from 'lucide-vue-next';

// 2. Props/emits
const emit = defineEmits<{
    (e: 'create'): void;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <FolderOpen class="size-12 text-muted-foreground" />
        <h3 class="mt-4 text-lg font-medium">Chưa có phòng nào</h3>
        <p class="text-muted-foreground">Bắt đầu tạo một phòng mới bằng cách nhấn nút "Thêm Phòng" hoặc import từ file JSON</p>
        <Button class="mt-4 bg-blue-500 text-white hover:bg-blue-600" @click="emit('create')"> Thêm Phòng </Button>
    </div>
</template>
