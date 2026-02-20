<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { WifiOff, Users, Home, AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { isOnline, useOfflineQueue } from '@/composables/useOfflineQueue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface MunicipalityCount {
    municipality: string;
    count: number;
}

interface RecentBeneficiary {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    municipality: string;
    barangay: string;
    created_at: string;
}

const props = defineProps<{
    total_beneficiaries: number;
    totally_damaged: number;
    partially_damaged: number;
    nhts_poor: number;
    nhts_near_poor: number;
    nhts_not_poor: number;
    by_municipality: MunicipalityCount[];
    recent_beneficiaries: RecentBeneficiary[];
}>();

const { pendingCount } = useOfflineQueue();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatName(b: RecentBeneficiary): string {
    const parts = [b.last_name, b.first_name];
    if (b.middle_name) parts.push(b.middle_name);
    return parts.join(', ');
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Online state -->
            <template v-if="isOnline">
                <!-- Row 1: Damage classification stats -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Beneficiaries</CardTitle>
                            <Users class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.total_beneficiaries.toLocaleString() }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Totally Damaged</CardTitle>
                            <Home class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.totally_damaged.toLocaleString() }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Partially Damaged</CardTitle>
                            <AlertTriangle class="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.partially_damaged.toLocaleString() }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Row 2: NHTS-PR classification stats -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">NHTS-PR Poor</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.nhts_poor.toLocaleString() }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">NHTS-PR Near Poor</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.nhts_near_poor.toLocaleString() }}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">NHTS-PR Not Poor</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.nhts_not_poor.toLocaleString() }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Row 3: Tables -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Municipality Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Municipality Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.by_municipality.length === 0" class="text-sm text-muted-foreground">
                                No data yet.
                            </div>
                            <table v-else class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="pb-2 text-left font-medium">Municipality</th>
                                        <th class="pb-2 text-right font-medium">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in props.by_municipality" :key="row.municipality" class="border-b last:border-0">
                                        <td class="py-2">{{ row.municipality }}</td>
                                        <td class="py-2 text-right">{{ row.count.toLocaleString() }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>

                    <!-- Recent Entries -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Entries</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.recent_beneficiaries.length === 0" class="text-sm text-muted-foreground">
                                No entries yet.
                            </div>
                            <table v-else class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="pb-2 text-left font-medium">Name</th>
                                        <th class="pb-2 text-left font-medium">Municipality</th>
                                        <th class="pb-2 text-right font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in props.recent_beneficiaries" :key="b.id" class="border-b last:border-0">
                                        <td class="py-2">{{ formatName(b) }}</td>
                                        <td class="py-2">{{ b.municipality }}</td>
                                        <td class="py-2 text-right whitespace-nowrap">{{ formatDate(b.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <!-- Offline state -->
            <template v-else>
                <div class="flex flex-1 items-center justify-center">
                    <Card class="w-full max-w-md text-center">
                        <CardContent class="flex flex-col items-center gap-4 pt-6">
                            <WifiOff class="size-12 text-muted-foreground" />
                            <div>
                                <h2 class="text-lg font-semibold">You're currently offline</h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Dashboard statistics will be available when you reconnect.
                                </p>
                            </div>
                            <p v-if="pendingCount > 0" class="text-sm text-muted-foreground">
                                {{ pendingCount }} entry{{ pendingCount === 1 ? '' : 'ies' }} pending sync.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
