<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { WifiOff, Users, Home, AlertTriangle, ShieldCheck, ShieldAlert, ShieldX, MapPin, Clock, Loader2, ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogHeader, DialogTitle, DialogScrollContent } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { isOnline, useOfflineQueue } from '@/composables/useOfflineQueue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface MunicipalityCount {
    municipality: string;
    count: number;
}

interface BarangayCount {
    municipality: string;
    barangay: string;
    count: number;
    totally_damaged: number;
    partially_damaged: number;
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
    by_barangay: BarangayCount[];
    recent_beneficiaries: RecentBeneficiary[];
}>();

const barangaysByMunicipality = computed(() => {
    const map: Record<string, BarangayCount[]> = {};
    for (const row of props.by_barangay) {
        if (!map[row.municipality]) map[row.municipality] = [];
        map[row.municipality].push(row);
    }
    return map;
});

const maxMunicipalityCount = computed(() => {
    return Math.max(...props.by_municipality.map((m) => m.count), 1);
});

function pct(value: number, total: number): string {
    if (total === 0) return '0';
    return ((value / total) * 100).toFixed(1);
}

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

function timeAgo(dateStr: string): string {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateStr);
}

// Colors for municipality bars
const barColors = [
    'bg-blue-500',
    'bg-emerald-500',
    'bg-amber-500',
    'bg-violet-500',
    'bg-rose-500',
    'bg-cyan-500',
    'bg-orange-500',
    'bg-teal-500',
];

// Dialog state for beneficiary list
interface BeneficiaryListItem {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    extension_name: string | null;
    sex: string;
    birth_date: string;
    classify_extent_of_damaged_house: string;
    purok: string | null;
}

const dialogOpen = ref(false);
const dialogLoading = ref(false);
const dialogTitle = ref('');
const dialogBeneficiaries = ref<BeneficiaryListItem[]>([]);

const dialogPage = ref(1);
const perPage = 10;
const dialogSearch = ref('');
const dialogDamageFilter = ref<'all' | 'totally' | 'partially'>('all');

const filteredBeneficiaries = computed(() => {
    let list = dialogBeneficiaries.value;

    if (dialogDamageFilter.value === 'totally') {
        list = list.filter((b) => b.classify_extent_of_damaged_house.startsWith('Totally'));
    } else if (dialogDamageFilter.value === 'partially') {
        list = list.filter((b) => b.classify_extent_of_damaged_house.startsWith('Partially'));
    }

    const q = dialogSearch.value.trim().toLowerCase();
    if (q) {
        list = list.filter((b) => {
            const name = `${b.last_name} ${b.first_name} ${b.middle_name ?? ''} ${b.extension_name ?? ''}`.toLowerCase();
            return name.includes(q) || (b.purok && b.purok.toLowerCase().includes(q));
        });
    }

    return list;
});

watch([dialogSearch, dialogDamageFilter], () => {
    dialogPage.value = 1;
});

const dialogTotalPages = computed(() => Math.max(1, Math.ceil(filteredBeneficiaries.value.length / perPage)));
const paginatedBeneficiaries = computed(() => {
    const start = (dialogPage.value - 1) * perPage;
    return filteredBeneficiaries.value.slice(start, start + perPage);
});
const visiblePages = computed(() => {
    const total = dialogTotalPages.value;
    const current = dialogPage.value;
    const pages: (number | '...')[] = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }

    pages.push(1);
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

async function openBeneficiaryList(municipality: string, barangay: string, damageType: 'totally' | 'partially' | 'all') {
    const labels: Record<string, string> = {
        totally: 'Totally Damaged',
        partially: 'Partially Damaged',
        all: 'All',
    };
    dialogTitle.value = `${barangay}, ${municipality} — ${labels[damageType]}`;
    dialogBeneficiaries.value = [];
    dialogPage.value = 1;
    dialogSearch.value = '';
    dialogDamageFilter.value = 'all';
    dialogLoading.value = true;
    dialogOpen.value = true;

    try {
        const { data } = await axios.get('/dashboard/beneficiaries-by-barangay', {
            params: { municipality, barangay, damage_type: damageType },
        });
        dialogBeneficiaries.value = data;
    } finally {
        dialogLoading.value = false;
    }
}

function fullName(b: BeneficiaryListItem): string {
    const parts = [b.last_name, b.first_name];
    if (b.middle_name) parts.push(b.middle_name);
    if (b.extension_name) parts.push(b.extension_name);
    return parts.join(', ');
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Online state -->
            <template v-if="isOnline">
                <!-- Summary Cards -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Total -->
                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-blue-500" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Beneficiaries</CardTitle>
                            <div class="rounded-lg bg-blue-500/10 p-2">
                                <Users class="size-4 text-blue-500" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.total_beneficiaries.toLocaleString() }}</div>
                            <p class="mt-1 text-xs text-muted-foreground">Total registered records</p>
                        </CardContent>
                    </Card>

                    <!-- Totally Damaged -->
                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-red-500" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Totally Damaged</CardTitle>
                            <div class="rounded-lg bg-red-500/10 p-2">
                                <Home class="size-4 text-red-500" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.totally_damaged.toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-red-500 transition-all duration-500"
                                        :style="{ width: pct(totally_damaged, total_beneficiaries) + '%' }"
                                    />
                                </div>
                                <span class="text-xs font-medium text-muted-foreground">{{ pct(totally_damaged, total_beneficiaries) }}%</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Partially Damaged -->
                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-amber-500" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Partially Damaged</CardTitle>
                            <div class="rounded-lg bg-amber-500/10 p-2">
                                <AlertTriangle class="size-4 text-amber-500" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.partially_damaged.toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-amber-500 transition-all duration-500"
                                        :style="{ width: pct(partially_damaged, total_beneficiaries) + '%' }"
                                    />
                                </div>
                                <span class="text-xs font-medium text-muted-foreground">{{ pct(partially_damaged, total_beneficiaries) }}%</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- NHTS-PR Cards -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-emerald-500" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">NHTS-PR Poor</CardTitle>
                            <div class="rounded-lg bg-emerald-500/10 p-2">
                                <ShieldCheck class="size-4 text-emerald-500" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.nhts_poor.toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                        :style="{ width: pct(nhts_poor, total_beneficiaries) + '%' }"
                                    />
                                </div>
                                <span class="text-xs font-medium text-muted-foreground">{{ pct(nhts_poor, total_beneficiaries) }}%</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-yellow-500" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">NHTS-PR Near Poor</CardTitle>
                            <div class="rounded-lg bg-yellow-500/10 p-2">
                                <ShieldAlert class="size-4 text-yellow-500" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.nhts_near_poor.toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-yellow-500 transition-all duration-500"
                                        :style="{ width: pct(nhts_near_poor, total_beneficiaries) + '%' }"
                                    />
                                </div>
                                <span class="text-xs font-medium text-muted-foreground">{{ pct(nhts_near_poor, total_beneficiaries) }}%</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <div class="absolute inset-y-0 right-0 w-1 bg-slate-400" />
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">NHTS-PR Not Poor</CardTitle>
                            <div class="rounded-lg bg-slate-400/10 p-2">
                                <ShieldX class="size-4 text-slate-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold tracking-tight">{{ props.nhts_not_poor.toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full bg-slate-400 transition-all duration-500"
                                        :style="{ width: pct(nhts_not_poor, total_beneficiaries) + '%' }"
                                    />
                                </div>
                                <span class="text-xs font-medium text-muted-foreground">{{ pct(nhts_not_poor, total_beneficiaries) }}%</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- LGU / Barangay Breakdown -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-violet-500/10 p-2">
                                <MapPin class="size-4 text-violet-500" />
                            </div>
                            <CardTitle>Breakdown by LGU / Barangay</CardTitle>
                        </div>
                        <Badge variant="secondary">{{ props.by_municipality.length }} LGUs</Badge>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.by_municipality.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No data yet.
                        </div>
                        <div v-else class="space-y-6">
                            <div v-for="(row, idx) in props.by_municipality" :key="row.municipality">
                                <!-- Municipality header -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="size-2.5 rounded-full" :class="barColors[idx % barColors.length]" />
                                            <span class="text-sm font-semibold">{{ row.municipality }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold">{{ row.count.toLocaleString() }}</span>
                                            <Badge variant="outline" class="text-xs">{{ pct(row.count, total_beneficiaries) }}%</Badge>
                                        </div>
                                    </div>
                                    <!-- Municipality progress bar -->
                                    <div class="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="barColors[idx % barColors.length]"
                                            :style="{ width: pct(row.count, maxMunicipalityCount) + '%' }"
                                        />
                                    </div>
                                    <!-- Barangay sub-rows -->
                                    <div
                                        v-if="barangaysByMunicipality[row.municipality]?.length"
                                        class="mt-3 overflow-hidden rounded-lg border"
                                    >
                                        <!-- Table header -->
                                        <div class="grid grid-cols-[1fr_72px_72px_60px] items-center gap-2 border-b bg-muted/50 px-4 py-2 text-xs font-semibold text-muted-foreground">
                                            <span>Barangay</span>
                                            <span class="flex items-center justify-center gap-1 text-red-500">
                                                <Home class="size-3" /> Totally
                                            </span>
                                            <span class="flex items-center justify-center gap-1 text-amber-500">
                                                <AlertTriangle class="size-3" /> Partially
                                            </span>
                                            <span class="text-center">Total</span>
                                        </div>
                                        <!-- Table rows -->
                                        <div
                                            v-for="(brgy, brgyIdx) in barangaysByMunicipality[row.municipality]"
                                            :key="brgy.barangay"
                                            class="grid cursor-pointer grid-cols-[1fr_72px_72px_60px] items-center gap-2 px-4 py-2.5 transition-colors hover:bg-muted/30"
                                            :class="{ 'border-b': brgyIdx < barangaysByMunicipality[row.municipality].length - 1 }"
                                            @click="openBeneficiaryList(brgy.municipality, brgy.barangay, 'all')"
                                        >
                                            <span class="truncate text-sm font-medium text-primary underline-offset-2 hover:underline">{{ brgy.barangay }}</span>
                                            <div class="flex justify-center">
                                                <span class="inline-flex min-w-[2.5rem] items-center justify-center rounded-md bg-red-500/10 px-2 py-0.5 text-xs font-bold text-red-600 dark:text-red-400">
                                                    {{ brgy.totally_damaged }}
                                                </span>
                                            </div>
                                            <div class="flex justify-center">
                                                <span class="inline-flex min-w-[2.5rem] items-center justify-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                                    {{ brgy.partially_damaged }}
                                                </span>
                                            </div>
                                            <div class="flex justify-center">
                                                <span class="inline-flex min-w-[2.5rem] items-center justify-center rounded-md bg-muted px-2 py-0.5 text-xs font-bold">
                                                    {{ brgy.count }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Entries -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-cyan-500/10 p-2">
                                <Clock class="size-4 text-cyan-500" />
                            </div>
                            <CardTitle>Recent Entries</CardTitle>
                        </div>
                        <Badge variant="secondary">Last 10</Badge>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.recent_beneficiaries.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No entries yet.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="b in props.recent_beneficiaries"
                                :key="b.id"
                                class="flex items-center justify-between rounded-lg border px-4 py-3 transition-colors hover:bg-muted/30"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ formatName(b) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ b.municipality }} &middot; {{ b.barangay }}</p>
                                </div>
                                <span class="ml-4 shrink-0 text-xs text-muted-foreground">{{ timeAgo(b.created_at) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
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

        <!-- Beneficiary list dialog -->
        <Dialog v-model:open="dialogOpen">
            <DialogScrollContent class="flex max-h-[90vh] max-w-5xl flex-col">
                <DialogHeader>
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <!-- Loading -->
                <div v-if="dialogLoading" class="flex items-center justify-center py-12">
                    <Loader2 class="size-6 animate-spin text-muted-foreground" />
                </div>

                <!-- Empty -->
                <div v-else-if="dialogBeneficiaries.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                    No beneficiaries found.
                </div>

                <!-- List -->
                <template v-else>
                    <!-- Search & filter -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="dialogSearch" placeholder="Search by name or purok..." class="pl-9" />
                        </div>
                        <select
                            v-model="dialogDamageFilter"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <option value="all">All Damage Types</option>
                            <option value="totally">Totally Damaged</option>
                            <option value="partially">Partially Damaged</option>
                        </select>
                    </div>

                    <!-- No results after filter -->
                    <div v-if="filteredBeneficiaries.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No matching beneficiaries found.
                    </div>

                    <div v-else class="min-h-0 flex-1 overflow-auto rounded-lg border">
                        <!-- Table header -->
                        <div class="grid grid-cols-[40px_minmax(0,1fr)_70px_100px_100px] items-center gap-2 border-b bg-muted/50 px-4 py-2 text-xs font-semibold text-muted-foreground">
                            <span class="text-center">#</span>
                            <span>Name</span>
                            <span class="text-center">Sex</span>
                            <span class="text-center">Birth Date</span>
                            <span class="text-center">Damage</span>
                        </div>
                        <!-- Table rows -->
                        <div
                            v-for="(b, i) in paginatedBeneficiaries"
                            :key="b.id"
                            class="grid grid-cols-[40px_minmax(0,1fr)_70px_100px_100px] items-center gap-2 px-4 py-2.5 transition-colors hover:bg-muted/30"
                            :class="{ 'border-b': i < paginatedBeneficiaries.length - 1 }"
                        >
                            <span class="text-center text-xs text-muted-foreground">{{ (dialogPage - 1) * perPage + i + 1 }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ fullName(b) }}</p>
                                <p v-if="b.purok" class="truncate text-xs text-muted-foreground">{{ b.purok }}</p>
                            </div>
                            <span class="text-center text-xs">{{ b.sex }}</span>
                            <span class="text-center text-xs">{{ formatDate(b.birth_date) }}</span>
                            <div class="flex justify-center">
                                <span
                                    class="inline-flex items-center justify-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                    :class="b.classify_extent_of_damaged_house.startsWith('Totally')
                                        ? 'bg-red-500/10 text-red-600 dark:text-red-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'"
                                >
                                    {{ b.classify_extent_of_damaged_house.startsWith('Totally') ? 'Totally' : 'Partially' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination footer -->
                    <div v-if="filteredBeneficiaries.length > 0" class="flex items-center justify-between pt-2">
                        <p class="text-xs text-muted-foreground">
                            <template v-if="filteredBeneficiaries.length !== dialogBeneficiaries.length">
                                {{ filteredBeneficiaries.length }} of {{ dialogBeneficiaries.length }} beneficiar{{ dialogBeneficiaries.length === 1 ? 'y' : 'ies' }}
                            </template>
                            <template v-else>
                                {{ dialogBeneficiaries.length }} beneficiar{{ dialogBeneficiaries.length === 1 ? 'y' : 'ies' }}
                            </template>
                        </p>
                        <div v-if="dialogTotalPages > 1" class="flex items-center gap-1">
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                :disabled="dialogPage <= 1"
                                @click="dialogPage--"
                            >
                                <ChevronLeft class="size-4" />
                            </button>
                            <template v-for="(p, i) in visiblePages" :key="i">
                                <span v-if="p === '...'" class="px-1 text-xs text-muted-foreground">...</span>
                                <button
                                    v-else
                                    class="inline-flex size-8 items-center justify-center rounded-md text-xs font-medium transition-colors"
                                    :class="p === dialogPage ? 'bg-primary text-primary-foreground' : 'border hover:bg-muted'"
                                    @click="dialogPage = p"
                                >
                                    {{ p }}
                                </button>
                            </template>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-50"
                                :disabled="dialogPage >= dialogTotalPages"
                                @click="dialogPage++"
                            >
                                <ChevronRight class="size-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </DialogScrollContent>
        </Dialog>
    </AppLayout>
</template>
