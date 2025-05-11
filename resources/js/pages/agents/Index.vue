<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Computer } from '@/types';
import { Head } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm as useVeeForm } from 'vee-validate';
import { ref } from 'vue';
import { z } from 'zod';

// Define types
interface AgentStats {
    total: number;
    online: number;
    offline: number;
    idle: number;
}

interface Props {
    stats: AgentStats;
    recentComputers: Computer[];
}

// Props
defineProps<Props>();

// Toast
const { toast } = useToast();

// Dialog state
const isUpdateDialogOpen = ref(false);

// Form validation schema
const updateSchema = toTypedSchema(
    z.object({
        version: z.string().optional(),
        force: z.boolean().default(false),
        restart_after: z.boolean().default(false),
    }),
);

// Form instance
const { handleSubmit, resetForm, values } = useVeeForm({
    validationSchema: updateSchema,
    initialValues: {
        version: '',
        force: false,
        restart_after: false,
    },
});

// Loading state
const isLoading = ref(false);

// Form submission handler
const onSubmit = handleSubmit(async (formValues) => {
    isLoading.value = true;

    try {
        const response = await axios.post(route('agents.update-all'), formValues);

        if (response.data.success) {
            toast({
                title: 'Success!',
                description: response.data.message,
                variant: 'default',
            });

            isUpdateDialogOpen.value = false;
            resetForm();
        } else {
            toast({
                title: 'Error',
                description: response.data.message || 'Failed to update agents',
                variant: 'destructive',
            });
        }
    } catch (error) {
        toast({
            title: 'Error',
            description: 'Failed to update agents. Please try again.',
            variant: 'destructive',
        });
    } finally {
        isLoading.value = false;
    }
});

// Computed status colors
const getStatusColor = (status: string): string => {
    switch (status.toLowerCase()) {
        case 'online':
            return 'bg-green-500';
        case 'offline':
            return 'bg-red-500';
        case 'idle':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-400';
    }
};
</script>

<template>
    <AppLayout title="Agents Management">
        <Head title="Agents Management" />

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Agents Management</h1>

                    <div>
                        <Dialog v-model:open="isUpdateDialogOpen">
                            <DialogTrigger as-child>
                                <Button variant="default">Update All Agents</Button>
                            </DialogTrigger>

                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Update All Agents</DialogTitle>
                                    <DialogDescription>
                                        This will send an update command to all online agents in the system. Are you sure?
                                    </DialogDescription>
                                </DialogHeader>

                                <form @submit.prevent="onSubmit" class="space-y-4 py-2">
                                    <div>
                                        <Label for="version">Version (optional)</Label>
                                        <Input id="version" v-model="values.version" placeholder="e.g., 1.2.3" />
                                        <p class="mt-1 text-sm text-gray-500">Leave blank to update to the latest version</p>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="force" v-model="values.force" />
                                        <Label for="force">Force update (ignore version check)</Label>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="restart" v-model="values.restart_after" />
                                        <Label for="restart">Restart agents after update</Label>
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isUpdateDialogOpen = false"> Cancel </Button>
                                        <Button type="submit" :disabled="isLoading">
                                            <span v-if="isLoading">Processing...</span>
                                            <span v-else>Update All</span>
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Agents</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-3xl font-bold">{{ stats.total }}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Online</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-3xl font-bold text-green-600">{{ stats.online }}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Offline</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-3xl font-bold text-red-600">{{ stats.offline }}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Idle</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-3xl font-bold text-yellow-500">{{ stats.idle }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Agents Table -->
                <div class="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Agents</CardTitle>
                            <CardDescription>Recently active or updated agents</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableCaption>Recently updated agents in the system</TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Room</TableHead>
                                        <TableHead>MAC Address</TableHead>
                                        <TableHead>IP Address</TableHead>
                                        <TableHead>Version</TableHead>
                                        <TableHead>Last Seen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="agent in recentComputers" :key="agent.id">
                                        <TableCell>
                                            <Badge :class="getStatusColor(agent.status)">
                                                {{ agent.status }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ agent.hostname }}</TableCell>
                                        <TableCell>{{ agent.room?.name ?? '—' }}</TableCell>
                                        <TableCell>{{ agent.mac_address }}</TableCell>
                                        <TableCell>{{ agent.version ?? '—' }}</TableCell>
                                        <TableCell>{{
                                            agent.last_heartbeat_at ? new Date(agent.last_heartbeat_at).toLocaleString() : '—'
                                        }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                        <CardFooter>
                            <div class="text-sm text-gray-500">Showing the most recent {{ recentComputers.length }} agents.</div>
                        </CardFooter>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
