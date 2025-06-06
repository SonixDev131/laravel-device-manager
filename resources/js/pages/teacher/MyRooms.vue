<template>
    <AppLayout title="My Rooms">
        <div class="p-6">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">My Assigned Rooms</h1>
                    <p class="mt-2 text-gray-400">Manage and control your assigned computer labs.</p>
                </div>

                <div v-if="rooms.length === 0" class="py-12 text-center">
                    <div class="text-lg text-gray-500">No rooms assigned to you yet.</div>
                    <p class="mt-2 text-gray-400">Contact your administrator to get room access.</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div v-for="room in rooms" :key="room.id" class="overflow-hidden rounded-lg bg-white shadow-md">
                        <!-- Room Header -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold">{{ room.name }}</h3>
                                    <p class="text-blue-100">{{ room.grid_rows }}×{{ room.grid_cols }} layout</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm opacity-90">Computers</div>
                                    <div class="text-2xl font-bold">{{ room.computers?.length || 0 }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Room Statistics -->
                        <div class="border-b border-gray-200 px-6 py-4">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ getOnlineComputers(room) }}</div>
                                    <div class="text-sm text-gray-500">Online</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ getOfflineComputers(room) }}</div>
                                    <div class="text-sm text-gray-500">Offline</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-yellow-600">{{ getLockedComputers(room) }}</div>
                                    <div class="text-sm text-gray-500">Locked</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="px-6 py-4">
                            <h4 class="mb-3 text-sm font-medium text-gray-700">Quick Actions</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-if="userPermissions.can_send_commands"
                                    @click="sendCommand(room, 'lock')"
                                    class="flex items-center justify-center rounded-md bg-yellow-100 px-3 py-2 text-yellow-800 transition-colors hover:bg-yellow-200"
                                >
                                    <LockClosedIcon class="mr-2 h-4 w-4" />
                                    Lock All
                                </button>

                                <button
                                    v-if="userPermissions.can_send_commands"
                                    @click="sendCommand(room, 'unlock')"
                                    class="flex items-center justify-center rounded-md bg-green-100 px-3 py-2 text-green-800 transition-colors hover:bg-green-200"
                                >
                                    <LockOpenIcon class="mr-2 h-4 w-4" />
                                    Unlock All
                                </button>

                                <button
                                    v-if="userPermissions.can_take_screenshots"
                                    @click="takeScreenshots(room)"
                                    class="flex items-center justify-center rounded-md bg-blue-100 px-3 py-2 text-blue-800 transition-colors hover:bg-blue-200"
                                >
                                    <CameraIcon class="mr-2 h-4 w-4" />
                                    Screenshots
                                </button>

                                <button
                                    v-if="userPermissions.can_send_commands"
                                    @click="sendCommand(room, 'restart')"
                                    class="flex items-center justify-center rounded-md bg-red-100 px-3 py-2 text-red-800 transition-colors hover:bg-red-200"
                                >
                                    <ArrowPathIcon class="mr-2 h-4 w-4" />
                                    Restart All
                                </button>
                            </div>
                        </div>

                        <!-- Room Actions -->
                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="route('rooms.show', room.id)"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                                >
                                    <EyeIcon class="mr-2 h-4 w-4" />
                                    View Room
                                </Link>

                                <button
                                    v-if="userPermissions.can_view_command_history"
                                    @click="openCommandHistoryDialog(room)"
                                    class="inline-flex items-center rounded-md bg-gray-600 px-4 py-2 text-white transition-colors hover:bg-gray-700"
                                >
                                    <ClockIcon class="mr-2 h-4 w-4" />
                                    History
                                </button>

                                <button
                                    v-if="userPermissions.can_block_websites"
                                    @click="openBlockedWebsitesDialog(room)"
                                    class="inline-flex items-center rounded-md bg-purple-600 px-4 py-2 text-white transition-colors hover:bg-purple-700"
                                >
                                    <ShieldExclamationIcon class="mr-2 h-4 w-4" />
                                    Block Sites
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Send Message Modal -->
                <div v-if="showMessageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="w-full max-w-md rounded-lg bg-white p-6">
                        <h3 class="mb-4 text-lg font-semibold">Send Message to {{ selectedRoom?.name }}</h3>
                        <textarea
                            v-model="messageText"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="4"
                            placeholder="Enter your message..."
                        ></textarea>
                        <div class="mt-4 flex justify-end gap-2">
                            <button
                                @click="showMessageModal = false"
                                class="rounded-md border border-gray-300 px-4 py-2 text-gray-600 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button @click="sendMessage" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Send Message</button>
                        </div>
                    </div>
                </div>

                <!-- Blocked Websites Dialog -->
                <Dialog v-model:open="showBlockedWebsitesDialog">
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <ShieldExclamationIcon class="h-5 w-5 text-purple-600" />
                                Blocked Websites - {{ selectedRoom?.name }}
                            </DialogTitle>
                            <DialogDescription> Manage website restrictions for all computers in this room </DialogDescription>
                        </DialogHeader>

                        <div class="mt-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-muted-foreground">Current website restrictions</h3>
                                <Button variant="outline" size="sm" @click="loadBlockedWebsites" :disabled="isLoadingWebsites">
                                    <ArrowPathIcon v-if="!isLoadingWebsites" class="mr-1 h-4 w-4" />
                                    <ArrowPathIcon v-else class="mr-1 h-4 w-4 animate-spin" />
                                    Refresh
                                </Button>
                            </div>

                            <div v-if="websitesError" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                                {{ websitesError }}
                            </div>

                            <div v-else-if="isLoadingWebsites" class="py-8 text-center">
                                <ArrowPathIcon class="mx-auto h-8 w-8 animate-spin text-muted-foreground" />
                                <p class="mt-2 text-sm text-muted-foreground">Loading blocked websites...</p>
                            </div>

                            <div v-else-if="blockedWebsites.length === 0" class="rounded-md border border-dashed border-gray-300 p-8 text-center">
                                <ShieldExclamationIcon class="mx-auto h-12 w-12 text-muted-foreground/60" />
                                <p class="mt-2 text-sm text-muted-foreground">No websites are currently blocked in this room.</p>
                                <p class="mt-1 text-xs text-muted-foreground">Use the form below to block websites for all computers.</p>
                            </div>

                            <div v-else class="rounded-md border border-gray-200">
                                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                    <h4 class="text-sm font-medium text-gray-900">Blocked Websites</h4>
                                    <p class="text-xs text-gray-600">These websites are blocked on all computers in this room</p>
                                </div>
                                <div class="max-h-60 overflow-y-auto">
                                    <div
                                        v-for="(website, index) in blockedWebsites"
                                        :key="index"
                                        class="flex items-center justify-between border-b border-gray-100 px-4 py-3 last:border-b-0"
                                    >
                                        <div class="flex items-center gap-2">
                                            <ShieldExclamationIcon class="h-4 w-4 text-purple-500" />
                                            <span class="text-sm text-gray-900">{{ website }}</span>
                                        </div>
                                        <Badge variant="outline" class="border-purple-200 bg-purple-50 text-purple-700">Blocked</Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Add New Blocked Websites Form -->
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                <h4 class="mb-3 text-sm font-medium text-gray-900">Block New Websites</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-600">Website URLs (one per line)</label>
                                        <Textarea
                                            v-model="newWebsitesToBlock"
                                            placeholder="Enter website URLs to block (e.g., facebook.com, youtube.com)"
                                            :rows="4"
                                            class="resize-none text-sm"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Enter one website per line. You can use domains (facebook.com) or full URLs.
                                        </p>
                                    </div>
                                    <Button
                                        @click="blockWebsites"
                                        :disabled="!newWebsitesToBlock.trim() || isBlockingWebsites"
                                        class="w-full bg-purple-600 hover:bg-purple-700"
                                    >
                                        <ArrowPathIcon v-if="isBlockingWebsites" class="mr-2 h-4 w-4 animate-spin" />
                                        <ShieldExclamationIcon v-else class="mr-2 h-4 w-4" />
                                        {{ isBlockingWebsites ? 'Blocking...' : 'Block Websites' }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="flex items-center justify-between">
                            <div class="text-xs text-muted-foreground">{{ blockedWebsites.length }} websites blocked</div>
                            <Button variant="outline" @click="showBlockedWebsitesDialog = false"> Close </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Command History Dialog -->
                <Dialog v-model:open="showCommandHistoryDialog">
                    <DialogContent class="sm:max-w-4xl">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <ClockIcon class="h-5 w-5 text-blue-600" />
                                Command History - {{ selectedRoom?.name }}
                            </DialogTitle>
                            <DialogDescription> View all commands sent in this room </DialogDescription>
                        </DialogHeader>

                        <div class="mt-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-muted-foreground">Recent commands</h3>
                                <Button variant="outline" size="sm" @click="loadCommandHistory" :disabled="isLoadingHistory">
                                    <ArrowPathIcon v-if="!isLoadingHistory" class="mr-1 h-4 w-4" />
                                    <ArrowPathIcon v-else class="mr-1 h-4 w-4 animate-spin" />
                                    Refresh
                                </Button>
                            </div>

                            <div v-if="historyError" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                                {{ historyError }}
                            </div>

                            <div v-else-if="isLoadingHistory" class="py-8 text-center">
                                <ArrowPathIcon class="mx-auto h-8 w-8 animate-spin text-muted-foreground" />
                                <p class="mt-2 text-sm text-muted-foreground">Loading command history...</p>
                            </div>

                            <div v-else-if="commandHistory.length === 0" class="py-8 text-center">
                                <ClockIcon class="mx-auto h-12 w-12 text-muted-foreground/60" />
                                <p class="mt-2 text-sm text-muted-foreground">No commands have been sent in this room yet.</p>
                            </div>

                            <div v-else class="overflow-hidden rounded-md border">
                                <div class="max-h-[50vh] overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 border-b bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Type</th>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Target</th>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Status</th>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Sent at</th>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Completed</th>
                                                <th class="px-4 py-3 text-left font-medium text-gray-900">Output</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="command in commandHistory" :key="command.id" class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-medium">{{ command.type }}</span>
                                                        <Badge v-if="command.is_group_command" variant="outline" class="text-xs">Group</Badge>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-gray-600">{{ command.target }}</span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <Badge :class="getStatusColor(command.status)">
                                                        {{ command.status }}
                                                    </Badge>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    {{ formatDate(command.created_at) }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    <span v-if="command.completed_at">{{ formatDate(command.completed_at) }}</span>
                                                    <span v-else class="text-gray-400">-</span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div v-if="command.output" class="flex items-center gap-2">
                                                        <span class="max-w-[200px] truncate text-xs text-gray-600" :title="command.output">
                                                            {{ command.output }}
                                                        </span>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            @click="showCommandOutput(command.output)"
                                                            class="h-6 w-6 p-0"
                                                        >
                                                            <EyeIcon class="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                    <span v-else class="text-gray-400">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="flex items-center justify-between">
                            <div class="text-xs text-muted-foreground">{{ commandHistory.length }} commands found</div>
                            <Button variant="outline" @click="showCommandHistoryDialog = false"> Close </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Command Output Dialog -->
                <Dialog v-model:open="showCommandOutputDialog">
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2 text-blue-600">
                                <EyeIcon class="h-5 w-5" />
                                Command Output Details
                            </DialogTitle>
                            <DialogDescription> The full output content for the command </DialogDescription>
                        </DialogHeader>

                        <div class="mt-4 max-h-[40vh] overflow-auto rounded-md border border-blue-200 bg-blue-50 p-4 text-blue-800">
                            <pre class="whitespace-pre-wrap text-sm">{{ selectedCommandOutput }}</pre>
                        </div>

                        <DialogFooter>
                            <Button variant="outline" @click="showCommandOutputDialog = false"> Close </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { ArrowPathIcon, CameraIcon, ClockIcon, EyeIcon, LockClosedIcon, LockOpenIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

defineProps({
    rooms: Array,
    userPermissions: Object,
});

const showMessageModal = ref(false);
const selectedRoom = ref(null);
const messageText = ref('');

// Blocked websites dialog state
const showBlockedWebsitesDialog = ref(false);
const blockedWebsites = ref([]);
const isLoadingWebsites = ref(false);
const websitesError = ref(null);
const newWebsitesToBlock = ref('');
const isBlockingWebsites = ref(false);

// Command history functionality
const showCommandHistoryDialog = ref(false);
const commandHistory = ref([]);
const isLoadingHistory = ref(false);
const historyError = ref(null);
const showCommandOutputDialog = ref(false);
const selectedCommandOutput = ref('');

const getOnlineComputers = (room) => {
    return room.computers?.filter((computer) => computer.latest_metric?.is_online).length || 0;
};

const getOfflineComputers = (room) => {
    return room.computers?.filter((computer) => !computer.latest_metric?.is_online).length || 0;
};

const getLockedComputers = (room) => {
    return room.computers?.filter((computer) => computer.latest_metric?.is_locked).length || 0;
};

const sendCommand = (room, command) => {
    if (command === 'message') {
        selectedRoom.value = room;
        showMessageModal.value = true;
        return;
    }

    const confirmMessage = {
        lock: 'Are you sure you want to lock all computers in this room?',
        unlock: 'Are you sure you want to unlock all computers in this room?',
        restart: 'Are you sure you want to restart all computers in this room? This will interrupt any ongoing work.',
    };

    if (confirm(confirmMessage[command])) {
        router.post(
            route('rooms.commands.publish', room.id),
            {
                command: command,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Optionally refresh room data or show success message
                },
            },
        );
    }
};

const sendMessage = () => {
    if (!messageText.value.trim()) return;

    router.post(
        route('rooms.commands.publish', selectedRoom.value.id),
        {
            command: 'message',
            message: messageText.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showMessageModal.value = false;
                messageText.value = '';
                selectedRoom.value = null;
            },
        },
    );
};

const takeScreenshots = (room) => {
    if (confirm('Take screenshots of all computers in this room?')) {
        router.post(
            route('rooms.commands.publish', room.id),
            {
                command: 'screenshot',
            },
            {
                preserveScroll: true,
            },
        );
    }
};

const openCommandHistoryDialog = (room) => {
    selectedRoom.value = room;
    showCommandHistoryDialog.value = true;
    loadCommandHistory();
};

const loadCommandHistory = async () => {
    if (!selectedRoom.value?.id) return;

    isLoadingHistory.value = true;
    historyError.value = null;

    try {
        const response = await axios.get(`/rooms/${selectedRoom.value.id}/commands`);
        commandHistory.value = response.data.data || [];
    } catch (err) {
        console.error('Failed to load command history:', err);
        historyError.value = 'Failed to load command history. Please try again.';
    } finally {
        isLoadingHistory.value = false;
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('default', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(date);
};

const getStatusColor = (status) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'sent':
        case 'in_progress':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'completed':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'failed':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const showCommandOutput = (output) => {
    selectedCommandOutput.value = output;
    showCommandOutputDialog.value = true;
};

// Blocked websites functionality
const openBlockedWebsitesDialog = (room) => {
    selectedRoom.value = room;
    showBlockedWebsitesDialog.value = true;
    loadBlockedWebsites();
};

const loadBlockedWebsites = async () => {
    if (!selectedRoom.value?.id) return;

    isLoadingWebsites.value = true;
    websitesError.value = null;

    try {
        const response = await axios.get(`/rooms/${selectedRoom.value.id}/blocked-websites`);
        blockedWebsites.value = response.data.data || [];
    } catch (err) {
        console.error('Failed to load blocked websites:', err);
        websitesError.value = 'Failed to load blocked websites. Please try again.';
    } finally {
        isLoadingWebsites.value = false;
    }
};

const blockWebsites = async () => {
    if (!selectedRoom.value?.id || !newWebsitesToBlock.value.trim()) return;

    isBlockingWebsites.value = true;
    websitesError.value = null;

    try {
        const websites = newWebsitesToBlock.value
            .split('\n')
            .map((url) => url.trim())
            .filter((url) => url.length > 0);

        if (websites.length === 0) {
            websitesError.value = 'Please enter at least one website URL.';
            return;
        }

        await router.post(
            route('rooms.commands.publish', selectedRoom.value.id),
            {
                command: 'block_websites',
                websites: websites,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    newWebsitesToBlock.value = '';
                    loadBlockedWebsites();
                },
                onError: () => {
                    websitesError.value = 'Failed to block websites. Please try again.';
                },
            },
        );
    } catch (err) {
        console.error('Failed to block websites:', err);
        websitesError.value = 'Failed to block websites. Please try again.';
    } finally {
        isBlockingWebsites.value = false;
    }
};
</script>
