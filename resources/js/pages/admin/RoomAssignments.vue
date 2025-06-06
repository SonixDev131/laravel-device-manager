<template>
    <AppLayout title="Room Assignments">
        <div class="p-6">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">Room Assignments</h1>
                    <p class="mt-2 text-muted-foreground">Assign teachers to rooms and manage their access.</p>
                </div>

                <!-- Assignment Form -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Assign Teacher to Room</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="assignTeacher" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="space-y-2">
                                <Label for="teacher">Teacher</Label>
                                <Select v-model="form.teacher_id" required>
                                    <SelectTrigger id="teacher">
                                        <SelectValue placeholder="Select a teacher..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                            {{ teacher.name }} ({{ teacher.email }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="room">Room</Label>
                                <Select v-model="form.room_id" required>
                                    <SelectTrigger id="room">
                                        <SelectValue placeholder="Select a room..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="room in rooms" :key="room.id" :value="room.id">
                                            {{ room.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label>Expires At (Optional)</Label>
                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="cn('w-full justify-start text-left font-normal', !selectedDate && 'text-muted-foreground')"
                                        >
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            <span>{{ selectedDate ? formatDatePicker(selectedDate) : 'Pick a date' }}</span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0" align="start">
                                        <Calendar
                                            v-model="selectedDate"
                                            :min-value="today(getLocalTimeZone())"
                                            initial-focus
                                            @update:model-value="onDateSelect"
                                        />
                                        <div class="border-t p-3">
                                            <div class="mb-2 flex items-center gap-2">
                                                <Label class="text-sm">Time:</Label>
                                                <Input v-model="selectedTime" type="time" class="flex-1" @input="updateFormDateTime" />
                                            </div>
                                            <div class="flex gap-2">
                                                <Button size="sm" @click="clearDate" variant="outline" class="flex-1"> Clear </Button>
                                                <Button size="sm" @click="setToday" variant="outline" class="flex-1"> Today </Button>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div class="flex items-end">
                                <Button type="submit" :disabled="form.processing" class="w-full">
                                    <span v-if="form.processing">Assigning...</span>
                                    <span v-else>Assign Teacher</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Current Assignments -->
                <Card>
                    <CardHeader>
                        <CardTitle>Current Assignments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Teacher</TableHead>
                                        <TableHead>Room</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Assigned At</TableHead>
                                        <TableHead>Expires At</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="assignment in assignments" :key="assignment.id">
                                        <TableCell>
                                            <div class="space-y-1">
                                                <div class="font-medium">{{ assignment.user.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ assignment.user.email }}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {{ assignment.room.name }}
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="isAssignmentActive(assignment) ? 'default' : 'destructive'">
                                                {{ isAssignmentActive(assignment) ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(assignment.assigned_at) }}
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ assignment.expires_at ? formatDate(assignment.expires_at) : 'Never' }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex space-x-2">
                                                <Button
                                                    v-if="assignment.is_active"
                                                    @click="toggleAssignment(assignment, false)"
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    Deactivate
                                                </Button>
                                                <Button v-else @click="toggleAssignment(assignment, true)" variant="outline" size="sm">
                                                    Activate
                                                </Button>
                                                <Button @click="removeAssignment(assignment)" variant="destructive" size="sm"> Remove </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import { router, useForm } from '@inertiajs/vue3';
import { getLocalTimeZone, today } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { ref } from 'vue';

interface Teacher {
    id: string;
    name: string;
    email: string;
}

interface Room {
    id: string;
    name: string;
}

interface Assignment {
    id: string;
    is_active: boolean;
    assigned_at: string;
    expires_at: string | null;
    user: Teacher;
    room: Room;
}

defineProps<{
    assignments: Assignment[];
    teachers: Teacher[];
    rooms: Room[];
}>();

const form = useForm({
    teacher_id: '',
    room_id: '',
    expires_at: '',
});

// Date picker reactive properties
const selectedDate = ref();
const selectedTime = ref('23:59');

const assignTeacher = () => {
    form.post(route('admin.assign-teacher'), {
        onSuccess: () => {
            form.reset();
            selectedDate.value = undefined;
            selectedTime.value = '23:59';
        },
    });
};

const toggleAssignment = (assignment: Assignment, isActive: boolean) => {
    router.patch(
        route('admin.update-assignment', assignment.id),
        {
            is_active: isActive,
        },
        {
            preserveScroll: true,
        },
    );
};

const removeAssignment = (assignment: Assignment) => {
    if (confirm('Are you sure you want to remove this assignment?')) {
        router.delete(route('admin.remove-assignment', assignment.id), {
            preserveScroll: true,
        });
    }
};

const isAssignmentActive = (assignment: Assignment): boolean => {
    if (!assignment.is_active) return false;
    if (!assignment.expires_at) return true;
    return new Date(assignment.expires_at) > new Date();
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString();
};

// Date picker methods
const formatDatePicker = (date: any): string => {
    if (!date) return '';
    const jsDate = new Date(date.year, date.month - 1, date.day);
    return jsDate.toLocaleDateString();
};

const onDateSelect = (date: any) => {
    selectedDate.value = date;
    updateFormDateTime();
};

const updateFormDateTime = () => {
    if (selectedDate.value && selectedTime.value) {
        const jsDate = new Date(selectedDate.value.year, selectedDate.value.month - 1, selectedDate.value.day);
        const [hours, minutes] = selectedTime.value.split(':');
        jsDate.setHours(parseInt(hours), parseInt(minutes), 0, 0);
        form.expires_at = jsDate.toISOString();
    } else if (selectedDate.value) {
        const jsDate = new Date(selectedDate.value.year, selectedDate.value.month - 1, selectedDate.value.day, 23, 59, 0, 0);
        form.expires_at = jsDate.toISOString();
    } else {
        form.expires_at = '';
    }
};

const clearDate = () => {
    selectedDate.value = undefined;
    selectedTime.value = '23:59';
    form.expires_at = '';
};

const setToday = () => {
    selectedDate.value = today(getLocalTimeZone());
    updateFormDateTime();
};
</script>
