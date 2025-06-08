<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { CalendarIcon, DownloadIcon, EyeIcon, ImageIcon, RefreshCwIcon, SearchIcon } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Screenshot {
    id: string;
    command_id: string;
    computer_id: string;
    computer_name: string;
    file_name: string;
    file_size: number;
    file_size_formatted: string;
    mime_type: string;
    file_url: string;
    taken_at: string;
    taken_at_formatted: string;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    roomId: string;
}>();

const screenshots = ref<Screenshot[]>([]);
const pagination = ref<Pagination | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

// Filters
const selectedComputerId = ref<string>('all');
const selectedCommandId = ref<string>('all');
const fromDate = ref<string>('');
const toDate = ref<string>('');
const searchQuery = ref<string>('');

// Dialog state
const showImageDialog = ref(false);
const selectedScreenshot = ref<Screenshot | null>(null);

// Computed
const filteredScreenshots = computed(() => {
    if (!searchQuery.value) return screenshots.value;

    const query = searchQuery.value.toLowerCase();
    return screenshots.value.filter(
        (screenshot) => screenshot.computer_name.toLowerCase().includes(query) || screenshot.file_name.toLowerCase().includes(query),
    );
});

const hasFilters = computed(() => {
    return selectedComputerId.value !== 'all' || selectedCommandId.value !== 'all' || fromDate.value || toDate.value;
});

// Methods
const loadScreenshots = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams({
            page: page.toString(),
        });

        if (selectedComputerId.value !== 'all') params.append('computer_id', selectedComputerId.value);
        if (selectedCommandId.value !== 'all') params.append('command_id', selectedCommandId.value);
        if (fromDate.value) params.append('from_date', fromDate.value);
        if (toDate.value) params.append('to_date', toDate.value);

        const response = await fetch(`/rooms/${props.roomId}/screenshots?${params}`);

        if (!response.ok) {
            throw new Error('Failed to load screenshots');
        }

        const data = await response.json();
        screenshots.value = data.data;
        pagination.value = data.pagination;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An error occurred';
        console.error('Failed to load screenshots:', err);
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    selectedComputerId.value = 'all';
    selectedCommandId.value = 'all';
    fromDate.value = '';
    toDate.value = '';
    searchQuery.value = '';
    loadScreenshots();
};

const viewScreenshot = (screenshot: Screenshot) => {
    selectedScreenshot.value = screenshot;
    showImageDialog.value = true;
};

const downloadScreenshot = (screenshot: Screenshot) => {
    const link = document.createElement('a');
    link.href = screenshot.file_url;
    link.download = screenshot.file_name;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

// Watchers
watch([selectedComputerId, selectedCommandId, fromDate, toDate], () => {
    loadScreenshots();
});

// Lifecycle
onMounted(() => {
    loadScreenshots();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Screenshots</h2>
                <p class="text-muted-foreground">View and manage screenshots from room computers</p>
            </div>
            <Button @click="loadScreenshots()" :disabled="loading" variant="outline" size="sm">
                <RefreshCwIcon class="mr-2 h-4 w-4" :class="{ 'animate-spin': loading }" />
                Refresh
            </Button>
        </div>

        <!-- Filters -->
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Filters</CardTitle>
                <CardDescription>Filter screenshots by computer, command, or date range</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Search -->
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <div class="relative">
                            <SearchIcon class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input id="search" v-model="searchQuery" placeholder="Search by computer or filename..." class="pl-8" />
                        </div>
                    </div>

                    <!-- Computer Filter -->
                    <div class="space-y-2">
                        <Label for="computer">Computer</Label>
                        <Select v-model="selectedComputerId">
                            <SelectTrigger>
                                <SelectValue placeholder="All computers" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All computers</SelectItem>
                                <!-- Add computer options dynamically -->
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div class="space-y-2">
                        <Label for="from-date">From Date</Label>
                        <div class="relative">
                            <CalendarIcon class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input id="from-date" v-model="fromDate" type="datetime-local" class="pl-8" />
                        </div>
                    </div>

                    <!-- Date To -->
                    <div class="space-y-2">
                        <Label for="to-date">To Date</Label>
                        <div class="relative">
                            <CalendarIcon class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input id="to-date" v-model="toDate" type="datetime-local" class="pl-8" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button v-if="hasFilters" @click="clearFilters" variant="outline" size="sm"> Clear Filters </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Error State -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-700">{{ error }}</div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div v-for="i in 8" :key="i" class="space-y-3">
                <Skeleton class="h-48 w-full rounded-lg" />
                <div class="space-y-2">
                    <Skeleton class="h-4 w-3/4" />
                    <Skeleton class="h-3 w-1/2" />
                </div>
            </div>
        </div>

        <!-- Screenshots Grid -->
        <div v-else-if="filteredScreenshots.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Card v-for="screenshot in filteredScreenshots" :key="screenshot.id" class="overflow-hidden">
                <div class="relative aspect-video bg-gray-100">
                    <img
                        :src="screenshot.file_url"
                        :alt="screenshot.file_name"
                        class="h-full w-full object-cover transition-transform hover:scale-105"
                        @click="viewScreenshot(screenshot)"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 cursor-pointer bg-black/0 transition-colors hover:bg-black/10" @click="viewScreenshot(screenshot)">
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity hover:opacity-100">
                            <EyeIcon class="h-8 w-8 text-white drop-shadow-lg" />
                        </div>
                    </div>
                </div>

                <CardContent class="p-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Badge variant="secondary" class="text-xs">
                                {{ screenshot.computer_name }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ screenshot.file_size_formatted }}
                            </span>
                        </div>

                        <h3 class="truncate text-sm font-medium" :title="screenshot.file_name">
                            {{ screenshot.file_name }}
                        </h3>

                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(screenshot.taken_at) }}
                        </p>

                        <div class="flex gap-2">
                            <Button @click="viewScreenshot(screenshot)" size="sm" variant="outline" class="flex-1">
                                <EyeIcon class="mr-1 h-3 w-3" />
                                View
                            </Button>
                            <Button @click="downloadScreenshot(screenshot)" size="sm" variant="outline" class="flex-1">
                                <DownloadIcon class="mr-1 h-3 w-3" />
                                Download
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <div v-else class="py-12 text-center">
            <ImageIcon class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-medium">No screenshots found</h3>
            <p class="mt-2 text-muted-foreground">
                {{ hasFilters ? 'Try adjusting your filters or' : '' }}
                Take some screenshots to see them here.
            </p>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-center space-x-2">
            <Button @click="loadScreenshots(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" variant="outline" size="sm">
                Previous
            </Button>

            <span class="text-sm text-muted-foreground">
                Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)
            </span>

            <Button
                @click="loadScreenshots(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                variant="outline"
                size="sm"
            >
                Next
            </Button>
        </div>

        <!-- Image Dialog -->
        <Dialog v-model:open="showImageDialog">
            <DialogContent class="max-w-4xl">
                <DialogHeader v-if="selectedScreenshot">
                    <DialogTitle>{{ selectedScreenshot.file_name }}</DialogTitle>
                    <DialogDescription>
                        From {{ selectedScreenshot.computer_name }} • {{ formatDate(selectedScreenshot.taken_at) }} •
                        {{ selectedScreenshot.file_size_formatted }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedScreenshot" class="max-h-[70vh] overflow-auto">
                    <img :src="selectedScreenshot.file_url" :alt="selectedScreenshot.file_name" class="h-auto w-full rounded-lg" />
                </div>

                <div v-if="selectedScreenshot" class="flex justify-end space-x-2">
                    <Button @click="downloadScreenshot(selectedScreenshot)" variant="outline">
                        <DownloadIcon class="mr-2 h-4 w-4" />
                        Download
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
