<script setup lang="ts">
import ScreenshotGallery from '@/components/rooms/ScreenshotGallery.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

interface Props {
    room: {
        id: string;
        name: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="`Screenshots - ${room.name}`" />

    <AppLayout>
        <div class="container mx-auto px-4 py-6">
            <div class="mb-6">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="/rooms" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600"> Rooms </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg
                                    class="mx-1 h-3 w-3 text-gray-400"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 6 10"
                                >
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <a :href="`/rooms/${room.id}`" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                                    {{ room.name }}
                                </a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg
                                    class="mx-1 h-3 w-3 text-gray-400"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 6 10"
                                >
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Screenshots</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <ScreenshotGallery :room-id="room.id" />
        </div>
    </AppLayout>
</template>
