<template>
    <AppLayout title="Manage Teachers">
        <div class="p-6">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Manage Teachers</h1>
                        <p class="mt-2 text-muted-foreground">Create and manage teacher accounts.</p>
                    </div>
                    <Button @click="openCreateDialog"> Create Teacher </Button>
                </div>

                <!-- Teachers List -->
                <Card>
                    <CardHeader>
                        <CardTitle>Teachers ({{ teachers.length }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="teachers.length === 0" class="py-8 text-center">
                            <p class="text-muted-foreground">No teachers found.</p>
                            <Button @click="openCreateDialog" class="mt-4"> Create First Teacher </Button>
                        </div>
                        <div v-else class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead>Room Assignments</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="teacher in teachers" :key="teacher.id">
                                        <TableCell>
                                            <div class="font-medium">{{ teacher.name }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm text-muted-foreground">{{ teacher.email }}</div>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(teacher.created_at) }}
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="outline" size="sm" @click="router.visit(route('admin.room-assignments'))">
                                                Manage Rooms
                                            </Button>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex space-x-2">
                                                <Button @click="deleteTeacher(teacher)" variant="destructive" size="sm"> Delete </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Create Teacher Dialog -->
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Create Teacher Account</DialogTitle>
                            <DialogDescription>Add a new teacher to the system.</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="createTeacher" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="create-name">Full Name</Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    type="text"
                                    placeholder="Enter teacher's full name"
                                    required
                                    :class="{ 'border-red-500': createForm.errors.name }"
                                />
                                <div v-if="createForm.errors.name" class="text-sm text-red-600">
                                    {{ createForm.errors.name }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="create-email">Email Address</Label>
                                <Input
                                    id="create-email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    required
                                    :class="{ 'border-red-500': createForm.errors.email }"
                                />
                                <div v-if="createForm.errors.email" class="text-sm text-red-600">
                                    {{ createForm.errors.email }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="create-password">Password</Label>
                                <Input
                                    id="create-password"
                                    v-model="createForm.password"
                                    type="password"
                                    placeholder="Enter password"
                                    required
                                    :class="{ 'border-red-500': createForm.errors.password }"
                                />
                                <div v-if="createForm.errors.password" class="text-sm text-red-600">
                                    {{ createForm.errors.password }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="create-password-confirmation">Confirm Password</Label>
                                <Input
                                    id="create-password-confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    placeholder="Confirm password"
                                    required
                                    :class="{ 'border-red-500': createForm.errors.password_confirmation }"
                                />
                                <div v-if="createForm.errors.password_confirmation" class="text-sm text-red-600">
                                    {{ createForm.errors.password_confirmation }}
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="cancelCreate">Cancel</Button>
                                <Button type="submit" :disabled="createForm.processing">
                                    <span v-if="createForm.processing">Creating...</span>
                                    <span v-else>Create Teacher</span>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Teacher {
    id: string;
    name: string;
    email: string;
    created_at: string;
    roles: Array<{ name: string }>;
}

defineProps<{
    teachers: Teacher[];
}>();

// Dialog state
const isCreateDialogOpen = ref(false);

// Create teacher form
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const openCreateDialog = () => {
    isCreateDialogOpen.value = true;
    createForm.reset();
    createForm.clearErrors();
};

const cancelCreate = () => {
    isCreateDialogOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const createTeacher = () => {
    createForm.post(route('admin.teachers.store'), {
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
        onError: () => {
            // Keep dialog open on error to show validation messages
        },
    });
};

const deleteTeacher = (teacher: Teacher) => {
    if (
        confirm(`Are you sure you want to delete teacher "${teacher.name}"? This action cannot be undone and will remove all their room assignments.`)
    ) {
        router.delete(route('admin.teachers.destroy', teacher.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};
</script>
