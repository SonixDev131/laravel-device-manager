<script setup lang="ts">
import UpdateAllAgentsDialog from '@/components/Agents/UpdateAllAgentsDialog.vue';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Agents Management',
        href: '#',
    },
];
</script>

<template>
    <Head title="Agents Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="space-y-6 p-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">Agents Management</h1>
                    <UpdateAllAgentsDialog />
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>System-wide Updates</CardTitle>
                            <CardDescription> Update all agent software across the entire system </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">
                                Use this feature to deploy updates to all agent software across all rooms. Updates can be forced or specific versions
                                can be targeted.
                            </p>
                        </CardContent>
                        <CardFooter>
                            <UpdateAllAgentsDialog />
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Agent Status</CardTitle>
                            <CardDescription> Overview of agent versions across the system </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">This feature will be available in a future release.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Agent Configuration</CardTitle>
                            <CardDescription> Configure default agent settings </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm">This feature will be available in a future release.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
