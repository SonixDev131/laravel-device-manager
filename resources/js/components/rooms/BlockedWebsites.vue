<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import axios from 'axios';
import { ArrowRightIcon, Globe, RefreshCw } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    roomId: string;
}>();

const emit = defineEmits<{
    blockWebsite: [urls: string[]];
}>();

const isOpen = ref(false);
const isLoading = ref(false);
const blockedWebsites = ref<string[]>([]);
const error = ref<string | null>(null);

// Load blocked websites
const loadBlockedWebsites = async () => {
    if (!props.roomId) return;

    isLoading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/rooms/${props.roomId}/blocked-websites`);
        blockedWebsites.value = response.data.data;
    } catch (err) {
        console.error('Failed to load blocked websites:', err);
        error.value = 'Failed to load blocked websites. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

// Watch for open state to load data
const handleOpen = (open: boolean) => {
    isOpen.value = open;
    if (open) {
        loadBlockedWebsites();
    }
};

// Handle button click for new website dialog
const handleBlockWebsiteClick = () => {
    // Close the sidebar
    isOpen.value = false;
    
    // Trigger the main block website command through the parent component
    // The parent will show the proper dialog to enter websites
    emit('blockWebsite', blockedWebsites.value);
};

// Load initial data
onMounted(() => {
    if (isOpen.value) {
        loadBlockedWebsites();
    }
});
</script>

<template>
    <Sheet v-model:open="isOpen" @update:open="handleOpen">
        <SheetTrigger asChild>
            <Button 
                variant="secondary" 
                size="icon" 
                class="h-12 w-12 rounded-full bg-orange-600 shadow-lg transition-all duration-200 hover:scale-105 hover:bg-orange-700"
                title="Blocked Websites"
            >
                <Globe class="h-6 w-6 text-white" />
            </Button>
        </SheetTrigger>
        <SheetContent side="right" class="sm:max-w-md">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <Globe class="h-5 w-5" />
                    Blocked Websites
                </SheetTitle>
                <SheetDescription>
                    Currently blocked websites for all computers in this room
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-muted-foreground">Room-wide website restrictions</h3>
                    <Button variant="outline" size="sm" @click="loadBlockedWebsites" :disabled="isLoading">
                        <RefreshCw v-if="!isLoading" class="mr-1 h-4 w-4" />
                        <span v-else class="mr-1 h-4 w-4 animate-spin">⟳</span>
                        Refresh
                    </Button>
                </div>

                <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-600">
                    {{ error }}
                </div>

                <div v-else-if="isLoading" class="py-8 text-center">
                    <div class="mx-auto h-8 w-8 animate-spin text-muted-foreground">⟳</div>
                    <p class="mt-2 text-sm text-muted-foreground">Loading blocked websites...</p>
                </div>

                <div v-else-if="blockedWebsites.length === 0" class="rounded-md border border-dashed p-8 text-center">
                    <Globe class="mx-auto h-10 w-10 text-muted-foreground/60" />
                    <p class="mt-2 text-sm text-muted-foreground">No websites are currently blocked in this room.</p>
                    <p class="mt-1 text-xs text-muted-foreground">Click the button below to block websites for all computers.</p>
                </div>

                <div v-else class="rounded-md border border-gray-200 bg-gray-50">
                    <div class="border-b border-gray-200 bg-gray-100 p-3">
                        <h4 class="text-sm font-medium">Blocked Websites</h4>
                        <p class="text-xs text-muted-foreground">These websites are blocked on all computers in this room</p>
                    </div>
                    <ul class="divide-y">
                        <li 
                            v-for="(website, index) in blockedWebsites" 
                            :key="index"
                            class="flex items-center justify-between p-3 hover:bg-gray-100"
                        >
                            <div class="flex items-center gap-2">
                                <Globe class="h-4 w-4 text-orange-500" />
                                <span class="text-sm">{{ website }}</span>
                            </div>
                            <Badge variant="outline" class="bg-orange-50 text-orange-700">Blocked</Badge>
                        </li>
                    </ul>
                </div>

                <div class="mt-4">
                    <Button 
                        variant="default" 
                        @click="handleBlockWebsiteClick" 
                        class="w-full gap-2"
                    >
                        <span>Manage Blocked Websites</span>
                        <ArrowRightIcon class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
